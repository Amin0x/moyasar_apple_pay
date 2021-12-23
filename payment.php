
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ApplePAY</title>
    <link rel="stylesheet" href="css/style.css">  
</head>
<body>

    

<div class="apple-pay-button apple-pay-button-white"></div>

<div class="apple-pay-button apple-pay-button-black"></div>

<div class="apple-pay-button apple-pay-button-white-with-line"></div>


<script>

function createPaymentMethodData(){
    let applepayrequest = {
        version : 5,
        merchantIdentifier: 'merchant.sa.ets',
        merchantCapabilities: ['supports3DS'],
        supportedNetworks:['mada', 'masterCard', 'visa'],
        countryCode: "SA",
        //requiredBillingContactFields:[],
        //billingContact:{},
        //requiredShippingContactFields:[],
        //shippingContact:{},
        //applicationData:"",
        //supportedCountries:[],
        //supportsCouponCode:false,
        //couponCode:"",
        //shippingContactEditingMode:{},
    };

    return [{
        supportedMethods:'https://apple.com/apple-pay',
        data: applepayrequest
    }];
}


function createPaymentDetailsInit(total){
    return {
        "total": {
            "label": "My Merchant",
            "amount": {
                "value": total,
                "currency": "SAR"
            }
        }
    };
}

function createPaymentOptions(){
    return {
        "requestPayerName": false,
        "requestBillingAddress": false,
        "requestPayerEmail": false,
        "requestPayerPhone": false,
        "requestShipping": false,
        "shippingType": "shipping"
    };
}

document.addEventListener("DOMContentLoaded", function() {
 
    //we use only one button
    const appButton = document.querySelectorAll('.apple-pay-button')[0];
    appButton.style.display = "none";
    
    if (window.ApplePaySession && ApplePaySession.canMakePayments()) {
        appButton.style.display = "inline-block";
    }
        

    appButton.addEventListener('click', function () {
        if (!window.PaymentRequest)
            return;
  
        var request = null;
        request = new PaymentRequest(   createPaymentMethodData(), createPaymentDetailsInit('1.00'), createPaymentOptions() );

        
        request.onmerchantvalidation  = function(event){
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
                event.complete(sessionPromise);
                
            } catch (error) {
                console.error(error); // We need to handle the error instead of just logging it to console.
            }            
            
        }
    
        request.show().then(function(paymentresponse){
            const token = paymentresponse.details.token;
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

                console.log('moyasar payment api response: ', payment);

                if(!payment || !payment.id){
                    await paymentresponse.complete("fail");
                    return;
                } 
                
                
                if (payment.status != 'paid') {
                    await paymentresponse.complete("fail");
                    return;
                }
                
                await paymentresponse.complete("success");
                alert('SUCCESS');

            } catch (error) {
                console.error(error);
            }

        }).catch(function(error){ console.error(error)});

      

    });
});
</script>

</body>
</html>
