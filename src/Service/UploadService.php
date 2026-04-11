<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\Upload;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
class UploadService
{
    public function __construct(
        private ElementManager $elementManager,
    ) {
    }

    public function persistUpload(Upload $upload): void
    {
        $element = $this->elementManager->getElementOrFail($upload->getId());

        $element->addProperty('uploadLength', $upload->getUploadLength());
        $element->addProperty('uploadOffset', $upload->getUploadOffset());
        $element->addProperty('uploadComplete', $upload->isUploadComplete());
        $element->addProperty('uploadTarget', $upload->getUploadTarget()); // cast to string?
        $element->addProperty('alreadyUploadedChunks', $upload->getAlreadyUploadedChunks());
        $element->addProperty('uploadOwner', $upload->getUploadOwner()); // cast to string?
        $element->addProperty('extension', $upload->getExtension());
        $element->addProperty('expires', $upload->getExpires()); // cast to something?

        $this->elementManager->merge($element);
        $this->elementManager->flush();
    }
}
