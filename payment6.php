
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ApplePAY</title>
    <link rel="stylesheet" href="css/style.css">
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
        console.log('Not Supported Version or Card Network');
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
                'supportsCredit'                
            ]
        };

       
        const session = new ApplePaySession(6, request);
        


        session.onvalidatemerchant = event => {
           
            let body = {
                // 'validationURL': event.validationURL
                "validationURL": "https://apple-pay-gateway.apple.com/paymentservices/paymentSession"
            };

            try {
                console.log('Response Obj: '+JSON.stringify(event));
    
                let response = await fetch('/merchantValidation', {
                    method: 'post',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });
               
                let merchantSession = await response.json();
                session.completeMerchantValidation(merchantSession);
                
            } catch (error) {
                console.error(error); // We need to handle the error instead of just logging it to console.
            }
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

            try {
                let response = await fetch('https://api.moyasar.com/v1/payments', {
                    method: 'post',
                    headers: { 'Content-Type': 'application/json'},
                    body: JSON.stringify(body)
                });

                let payment = await response.json();

                if(!payment || !payment.id){
                    console.log('moyasar payment api response: ', payment);
                    session.completePayment({
                        status: ApplePaySession.STATUS_FAILURE
                    });

                    return;
                }

                if (payment.status != 'paid') {
                    session.completePayment({
                        status: ApplePaySession.STATUS_FAILURE,
                        errors: [
                            payment.source.message
                        ]
                    });

                    console.log('payment Obj Error: '+payment.source.message);
                    return;
                }

                session.completePayment({
                    status: ApplePaySession.STATUS_SUCCESS
                });

                //window.location = ''
				alert('SUCCESS');
            } catch (error) {
                session.completePayment({
                    status: ApplePaySession.STATUS_FAILURE,
                    errors: [ error.toString() ]
                });
                console.log('moyasar payment api error: ', error);
            }

            
         
        }
    

      

        session.begin();

    });
});
</script>

</body>
</html>
