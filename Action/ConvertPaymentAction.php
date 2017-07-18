<?php

namespace Villafinder\Payum2c2p\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;
use Payum\ISO4217\Currency;
use Payum\ISO4217\ISO4217;

class ConvertPaymentAction implements ActionInterface
{
    /**
     * @param Convert $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();

        $iso4217 = new ISO4217();
        /** @var Currency $currency */
        $currency = $iso4217->findByCode($payment->getCurrencyCode());

        $details = ArrayObject::ensureArrayObject($payment->getDetails());
        $details['payment_description'] = $payment->getDescription();
        $details['currency']            = $currency->getNumeric();
        $details['amount']              = sprintf('%012d', $payment->getTotalAmount());
        $details['customer_email']      = $payment->getClientEmail();
        $details['order_id']            = $payment->getNumber();

        $request->setResult((array) $details);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() == 'array'
        ;
    }
}
