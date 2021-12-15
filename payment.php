
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ApplePAY</title>
   
       
    <script src="https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js"></script>

    <style>
    apple-pay-button {
    --apple-pay-button-width: 140px;
    --apple-pay-button-height: 30px;
    --apple-pay-button-border-radius: 5px;
    --apple-pay-button-padding: 5px 0px;
    }
    </style>   
       

   
</head>
<body>
<apple-pay-button buttonstyle="black" type="buy" locale="en-US"></apple-pay-button>
<script>



document.addEventListener("DOMContentLoaded", function() {
 

    // if (window.ApplePaySession && ApplePaySession.canMakePayments()) {
    //     let el = document.querySelector('apple-pay-warrper');
    //     el.classList.remove('hide');
    // }

    document.querySelector('.apple-pay-button-with-text').addEventListener('click', function () {
        const request = {
            currencyCode: 'SAR',
            countryCode: 'SA',
            supportedCountries: ['SA'],
            total: { label: "My Awesome Shop", amount: '1.00' },
            supportedNetworks: ['masterCard', 'visa', 'mada'],
            merchantCapabilities: ['supports3DS', 'supportsCredit', 'supportsDebit']
        };


        const session = new ApplePaySession(6, request);

        session.onvalidatemerchant = event => {
            let merchantBackendUrl = 'https://ets.sa/merchant-validation';
            let body = {
                'validationUrl': event.validationURL
            };

            fetch(merchantBackendUrl, {
                method: 'post',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            })
            .then(response => response.json())
            .then(merchantSession => session.completeMerchantValidation(merchantSession))
            .catch(error => console.error(error)); // We need to handle the error instead of just logging it to console.
        }

        session.onpaymentauthorized = event => {
            const token = event.payment.token;
    
            let body = {
                'amount': 100, //  Halalas 
                'description': 'My Awsome Order #1234',
                'publishable_api_key': 'pk_live_2Hr3k1KnKmQda8DdS9Pkb8Wh9uG59ao8Aoja8Fvm',
                'source': {
                    'type': 'applepay',
                    'token': token
                }
            };


            fetch('https://api.moyasar.com/v1/payments', {
                method: 'post',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            })
            .then(response => response.json())
            .then(payment => {
                if (!payment.id) {
                    // TODO: Handle validation or API authorization error
                    // session.completePayment({
                    //     status: ApplePaySession.STATUS_FAILURE
                    // });
                    //window.location = ''
                }

                if (payment.status != 'paid') {
                    session.completePayment({
                        status: ApplePaySession.STATUS_FAILURE,
                        errors: [
                            payment.source.message
                        ]
                    });

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
            });
        }
    

      

        session.begin();

    });
});
</script>

</body>
</html>
