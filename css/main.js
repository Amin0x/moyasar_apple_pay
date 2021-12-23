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

function fetchPaymentSession(validationURL){
    var response = null;
    var body = {
        "validationURL" : validationURL,
    };
    var data = {
        method: 'post',
        headers: { 'Content-Type': 'application/json'},
        body: JSON.stringify(body)
    };

    try {
        response = await fetch("/merchantValidation", data);
        return response.json(); // Parse response as JSON.
        
    } catch (error) {        
        console.error("Error fetching merchant session", error);
    }
                    
}

async function fetchMoyasarPayments(token, amount, session){
    const api_token = 'pk_live_2Hr3k1KnKmQda8DdS9Pkb8Wh9uG59ao8Aoja8Fvm';

    let body = {
        'amount': 100, //  Halalas 
        'description': 'My Order',
        'publishable_api_key': api_token,
        'source': {
            'type': 'applepay',
            'token': token
        }
    };

    let option =  {
        method: 'post',
        headers: { 'Content-Type': 'application/json'},
        body: JSON.stringify(body)
    };

    try {
        const response = await fetch('https://api.moyasar.com/v1/payments', option);
        return await response.json();
    } catch (error) {
        if (session) {
            session.completePayment({
                status: ApplePaySession.STATUS_FAILURE,
                errors: [error.toString()]
            });
        }

        console.log('moyasar payment api error: ', error);
    }
}

function createWC3PaymentRequest(){
    if (!window.PaymentRequest)
        return;
  
    var request = null;
    request = new PaymentRequest(   createPaymentMethodData(), createPaymentDetailsInit('1.00'), createPaymentOptions() );

        
    request.onmerchantvalidation = function(event){
        const sessionPromise = fetchPaymentSession(event.validationURL);
        event.complete(sessionPromise);
    }
    
    request.show().then(function(paymentresponse){
        const token = paymentresponse.details.token;            
        let payment = await fetchMoyasarPayments(token, '100');
        
        if(!payment || !payment.id){
            paymentresponse.complete("fail");
        } else {
            paymentresponse.complete("success");
        }
    }).catch(function(error){ console.log(error)});
}

function createApplePayJs(){
    if (!window.ApplePaySession && !ApplePaySession.canMakePayments())
        return;
     

    var data = {
        currencyCode: 'SAR',
        countryCode: 'SA',
        total: { label: "My Awesome Shop", amount: '1.00', type: 'final' },
        supportedNetworks: ['masterCard', 'visa', 'mada'],
        merchantCapabilities: ['supports3DS', 'supportsCredit', 'supportsDebit']
    };

    var request = new ApplePaySession(6,data);

    request.onmerchantvalidation = function(event){
        const sessionPromise = fetchPaymentSession(event.validationURL);
        request.completeMerchantValidation(await sessionPromise)
    }

    request.onpaymentauthorized = function(event){
        const token = event.payment.token;
        fetchMoyasarPayments(token, 100, request).then(payment => {
            console.log('moyasar payment api response: ', payment);
            if (!payment.id) {
                // TODO: Handle validation or API authorization error
                // session.completePayment({
                //     status: ApplePaySession.STATUS_FAILURE
                // });
                //window.location = ''
            }

            if (payment.status != 'paid') {
                request.completePayment({
                    status: ApplePaySession.STATUS_FAILURE,
                    errors: [
                        payment.source.message
                    ]
                });

                return;
            }

            request.completePayment({
                status: ApplePaySession.STATUS_SUCCESS
            });

            //window.location = ''
            alert('SUCCESS');
        });
    }
    request.began();

}

document.addEventListener("DOMContentLoaded", function() {
 
    //we use only one button
    const appButton = document.querySelectorAll('.apple-pay-button')[0];
    appButton.style.display = "none";
    
    if ((window.ApplePaySession && ApplePaySession.canMakePayments()) || (window.PaymentRequest)) {
        appButton.style.display = "inline-block";
    }

    appButton.addEventListener('click', function(e){
        e.preventDefault();

        try{
            createWC3PaymentRequest();
        }catch(e){
            console.log('error :' + e)
        }

        try{
            createApplePayJs();
        }catch(e){
            console.log('error :' + e)
        }
    
    }); 

});