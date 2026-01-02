<?php

declare(strict_types=1);

namespace App\Factory\Type;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Type\Upload;
use DateTime;
use Exception;

class UploadFactory
{
    public function getUploadFromElement(NodeElementInterface|RelationElementInterface $element): Upload
    {
        $upload = new Upload();

        if (!($element instanceof NodeElementInterface)) {
            throw new Exception('Upload element must be a node, not a relation.');
        }

        $label = $element->getLabel() ?? 'no-label';
        if ('Upload' !== $label) {
            throw new Exception(sprintf('Can not cast element of type %s to upload.', $label));
        }

        $properties = $element->getProperties();

        if (array_key_exists('uploadLength', $properties)) {
            $uploadLength = $properties['uploadLength'];
            if (is_int($uploadLength)) {
                $upload->setUploadLength($uploadLength);
            }
        }

        if (array_key_exists('uploadOffset', $properties)) {
            $uploadOffset = $properties['uploadOffset'];
            if (is_int($uploadOffset)) {
                $upload->setUploadOffset($uploadOffset);
            }
        }

        if (array_key_exists('uploadComplete', $properties)) {
            $uploadComplete = $properties['uploadComplete'];
            if (is_bool($uploadComplete)) {
                $upload->setUploadComplete($uploadComplete);
            }
        }

        if (array_key_exists('created', $properties)) {
            $created = $properties['created'];
            if ($created instanceof DateTime) {
                $upload->setCreated($created);
            }
        }

        if (array_key_exists('updated', $properties)) {
            $updated = $properties['updated'];
            if ($updated instanceof DateTime) {
                $upload->setUpdated($updated);
            }
        }

        if (array_key_exists('expires', $properties)) {
            $expires = $properties['expires'];
            if ($expires instanceof DateTime) {
                $upload->setExpires($expires);
            }
        }

        return $upload;
    }
}
