<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\UploadInterface;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Type\NodeElement;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
class UploadService
{
    public function __construct(
        private ElementManager $elementManager,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
    }

    public function mergeUploadElement(UploadInterface $upload): void
    {
        $element = $this->elementManager->getElement($upload->getId());
        if (null !== $element) {
            if (!($element instanceof NodeElementInterface)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected upload element to be a node, received %s.', get_debug_type($element)));
            }
            if ('Upload' !== $element->getLabel()) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf("Expected upload element to be of type 'Upload', not '%s'.", $element->getLabel() ?? 'null'));
            }
        } else {
            $element = (new NodeElement())
                ->setId($upload->getId())
                ->setLabel('Upload');
        }

        $element->addProperty('uploadLength', $upload->getUploadLength());
        $element->addProperty('uploadOffset', $upload->getUploadOffset());
        $element->addProperty('uploadComplete', $upload->isUploadComplete());
        $element->addProperty('uploadTarget', $upload->getUploadTarget()->toString());
        $element->addProperty('alreadyUploadedChunks', $upload->getAlreadyUploadedChunks());
        $element->addProperty('uploadOwner', $upload->getUploadOwner()->toString());
        $element->addProperty('extension', $upload->getExtension());
        $element->addProperty('expires', $upload->getExpires());
        $element->addProperty('hashState', $upload->getHashState());

        $this->elementManager->merge($element);
    }

    public function deleteUpload(UploadInterface $upload): void
    {
        $element = $this->elementManager->getElementOrFail($upload->getId());

        $this->elementManager->delete($element);
    }
}
