<?php

namespace Villafinder\Payum2c2p\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\LogicException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Capture;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Villafinder\Payum2c2p\Api;

/**
 * @property Api $api
 */
class CaptureOnsiteNullAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;
    use CheckRequestOnsiteTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    /**
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);

        if (!$this->isBackFrom2c2p($httpRequest)) {
            return;
        }

        if (!$this->api->trustUserRequest()) {
            return;
        }

        try {
            $response = $this->readFromRequest($httpRequest);
        } catch (LogicException $e) {
            throw new HttpResponse($e->getMessage(), 400);
        }

        $redirectIndex = sprintf('userDefined%d', CaptureOnsiteAction::USER_DEFINED_URL);

        if (!isset($response[$redirectIndex])) {
            throw new HttpResponse('Redirect URL is missing from 2C2P response.', 400);
        }

        $redirectUrl = $response[$redirectIndex];

        if (!filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
            throw new HttpResponse('Redirect URL is invalid.', 400);
        }

        throw new HttpPostRedirect(
            $redirectUrl,
            $httpRequest->request
        );
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            null === $request->getModel()
        ;
    }
}
