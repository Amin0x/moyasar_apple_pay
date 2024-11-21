const applePayButton = document.getElementById('apple-pay-button');

function isApplePaySupported() {
  return window.ApplePaySession && ApplePaySession.canMakePayments();
}

function createPaymentMethodData() {
  return [{
    supportedMethods: 'https://apple.com/apple-pay',
    data: {
      version: 5,
      merchantIdentifier: 'merchant.sa.ets',
      merchantCapabilities: ['supports3DS'],
      supportedNetworks: ['mada', 'masterCard', 'visa'],
      countryCode: "SA",
    }
  }];
}

function createPaymentDetailsInit(total) {
  return {
    total: {
      label: "My Merchant",
      amount: {
        value: total,
        currency: "SAR"
      }
    }
  };
}

function createPaymentOptions() {
  return {
    requestPayerName: false,
    requestBillingAddress: false,
    requestPayerEmail: false,
    requestPayerPhone: false,
    requestShipping: false,
  };
}

function handlePaymentRequest(paymentRequest) {
  paymentRequest.onmerchantvalidation = async (event) => {
    try {
      const validationResponse = await fetch('/merchantValidation', {
        method: 'post',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ validationURL: event.validationURL })
      });
      const merchantSession = await validationResponse.json();
      event.complete(merchantSession);
    } catch (error) {
      console.error(error);
    }
  };

  paymentRequest.show()
    .then(async (paymentResponse) => {
      const token = paymentResponse.details.token;
      const apiToken = 'pk_live_2Hr3k1KnKmQda8DdS9Pkb8Wh9uG59ao8Aoja8Fvm';

      const body = {
        amount: 100, // Halalas
        description: 'My Awsome Order #1234',
        publishable_api_key: apiToken,
        source: {
          type: 'applepay',
          token: token
        }
      };

      try {
        const response = await fetch('https://api.moyasar.com/v1/payments', {
          method: 'post',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(body)
        });
        const payment = await response.json();

        console.log('moyasar payment api response: ', payment);

        if (!payment || !payment.id) {
          paymentResponse.complete("fail");
          return;
        }

        if (payment.status !== 'paid') {
          paymentResponse.complete("fail");
          return;
        }

        paymentResponse.complete("success");
        alert('SUCCESS');

      } catch (error) {
        console.error(error);
      }
    })
    .catch((error) => console.error(error));
}

document.addEventListener("DOMContentLoaded", function() {
  if (isApplePaySupported()) {
    applePayButton.style.display = "inline-block";
  }

  applePayButton.addEventListener('click', function () {
    if (!window.PaymentRequest) return;

    const request = new PaymentRequest(
      createPaymentMethodData(),
      createPaymentDetailsInit(1.00), // adjust total amount dynamically if needed
      createPaymentOptions()
    );

    handlePaymentRequest(request);
