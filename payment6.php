
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ApplePAY</title>
    <style>
        @supports (-webkit-appearance: -apple-pay-button) { 
            .apple-pay-button {
                display: inline-block;
                -webkit-appearance: -apple-pay-button;
            }
            .apple-pay-button-black {
                -apple-pay-button-style: black;
            
            }
            .apple-pay-button-white {
                -apple-pay-button-style: white;
            }
            .apple-pay-button-white-with-line {
                -apple-pay-button-style: white-outline;
            }
        }

        @supports not (-webkit-appearance: -apple-pay-button) {
            .apple-pay-button {
                display: inline-block;
                background-size: 100% 60%;
                background-repeat: no-repeat;
                background-position: 50% 50%;
                border-radius: 5px;
                padding: 0px;
                box-sizing: border-box;
                min-width: 200px;
                min-height: 32px;
                max-height: 64px;      
            }
            .apple-pay-button-black {
                background-image: -webkit-named-image(apple-pay-logo-white);
                background-color: black;
            }
            .apple-pay-button-white {
                background-image: -webkit-named-image(apple-pay-logo-black);
                background-color: white;
            }
            .apple-pay-button-white-with-line {
                background-image: -webkit-named-image(apple-pay-logo-black);
                background-color: white;
                border: .5px solid black;
            } 
        }
        

    </style>
</head>
<body>

    

<div class="apple-pay-button apple-pay-button-white"></div>

<div class="apple-pay-button apple-pay-button-black"></div>

<div class="apple-pay-button apple-pay-button-white-with-line"></div>

<div id="error_pay"></div>

<script>



document.addEventListener("DOMContentLoaded", function() {
 
    
    //we use only one button
    const appButton = document.querySelectorAll('.apple-pay-button')[0];
    appButton.style.display = "none";
    
    function addError(s){
        const error_pay =  document.getElementById('error_pay');
        let e = document.createElement("p");
        e.innerHTML = s;
        error_pay.append(e);
    }


    if (window.ApplePaySession && ApplePaySession.canMakePayments()) {
        appButton.style.display = "inline-block";
        
    } else {
        addError('Not Supported Version or Card Network');
    }
    
    

    appButton.addEventListener('click', function () {
        const request = {
            currencyCode: 'SAR',
            countryCode: 'SA',
            total: { 
                label: "My Awesome Shop", 
                amount: '1.00', 
                type: 'final' 
            },
            supportedNetworks: [
                'masterCard',
                 'visa',
                  'mada'
                ],
            merchantCapabilities: [
                'supports3DS', 
                'supportsCredit', 
                'supportsDebit'
            ]
        };

        if(ApplePaySession.supportedVersion(6)){
            const session = new ApplePaySession(6, request);
        } else {
            const session = new ApplePaySession(3, request);
        }


        session.onvalidatemerchant = event => {
            let merchantBackendUrl = 'https://ets.sa/merchant-validation';
            let body = {
                // 'validationUrl': event.validationURL
                "validationUrl": "https://apple-pay-gateway.apple.com/paymentservices/paymentSession"
            };

            addError('Response Obj: '+JSON.stringify(event));

            fetch(merchantBackendUrl, {
                method: 'post',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            })
            .then(response => {
                response.text().then(text => { addError('Response Obj: '+text) });               
                return response.json()
            } )
            .then(merchantSession => session.completeMerchantValidation(merchantSession))
            .catch(error => console.error(error)); // We need to handle the error instead of just logging it to console.
        }

        session.onpaymentauthorized = event => {
            const token = event.payment.token;
            const api_token = 'pk_live_2Hr3k1KnKmQda8DdS9Pkb8Wh9uG59ao8Aoja8Fvm';

            let body = {
                'amount': 100, //  Halalas 
                'description': 'My Awsome Order #1234',
                'publishable_api_key': api_token,
                'source': {
                    'type': 'applepay',
                    'token': token
                }
            };


            fetch('https://api.moyasar.com/v1/payments', {
                method: 'post',
                headers: { 'Content-Type': 'application/json', 'Authorization': 'Basic ' + api_token + ':' },
                body: JSON.stringify(body)
            })
            .then(response => response.json())
            .then(payment => {
                addError('payment Obj dump: '+JSON.stringify(payment));
                console.log('moyasar payment api response: ', payment);
                if (!payment.id) {
                    // TODO: Handle validation or API authorization error
                    // session.completePayment({
                    //     status: ApplePaySession.STATUS_FAILURE
                    // });
                    //window.location = ''
                    addError('payment Obj Error: '+JSON.stringify(payment));
                }

                if (payment.status != 'paid') {
                    session.completePayment({
                        status: ApplePaySession.STATUS_FAILURE,
                        errors: [
                            payment.source.message
                        ]
                    });

                    addError('payment Obj Error: '+payment.source.message);
                    return;
                }

                

                session.completePayment({
                    status: ApplePaySession.STATUS_SUCCESS
                });

                //window.location = ''
				alert('SUCCESS');
            })
            .catch(error => {
                session.completePayment({
                    status: ApplePaySession.STATUS_FAILURE,
                    errors: [ error.toString() ]
                });
                addError('moyasar payment api error: : '+error);
                console.log('moyasar payment api error: ', error);
            });
        }
    

      

        session.begin();

    });
});
</script>

</body>
</html>
