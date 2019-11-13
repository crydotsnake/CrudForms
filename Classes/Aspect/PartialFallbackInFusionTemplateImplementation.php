<?php

namespace Sandstorm\CrudForms\Aspect;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException;
use Neos\Fusion\FusionObjects\Helpers\FluidView;

/**
 * When using CrudForms in Fusion (NOT in a Neos Plugin); but directly Fusion & Flow; the partial path overriding
 * of ExtendedTemplateView is not used (as the View there is the FusionView).
 *
 * The Fusion TemplateImplementation uses the "Helpers\FluidView" class, which extends from StandaloneView.
 *
 * Here, we are monkey-patching the Helpers\FluidView class to re-implement the partial fallback:
 * - we are catching the InvalidTemplateResourceException in case a partial is not found.
 * - we are re-trying to resolve the partial in the Sandstorm.CrudForms package
 * - if this fails, we throw the *original* exception (as everything else would be confusing to the user)
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class PartialFallbackInFusionTemplateImplementation
{
    /**
     * Logs calls and results of the authenticate() method of the Authentication Manager
     *
     * @Flow\Around("method(Neos\Fusion\FusionObjects\Helpers\FluidView->getPartialPathAndFilename())")
     * @param JoinPointInterface $joinPoint The current joinpoint
     * @return mixed The result of the target method if it has not been intercepted
     * @throws \Exception
     */
    public function getPartialPathAndFilenameWithFallback(JoinPointInterface $joinPoint) {
        try {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        } catch (InvalidTemplateResourceException $e) {
            /* @var FluidView $fluidView */
            $fluidView = $joinPoint->getProxy();

            $partialRootPathBefore = $fluidView->getPartialRootPath();
            $fluidView->setPartialRootPath('resource://Sandstorm.CrudForms/Private/Partials/');
            try {
                $partialNow = $joinPoint->getAdviceChain()->proceed($joinPoint);
                $fluidView->setPartialRootPath($partialRootPathBefore);
                return $partialNow;
            } catch (InvalidTemplateResourceException $e2) {
                $fluidView->setPartialRootPath($partialRootPathBefore);
                throw $e;
            }
        }
    }
}
