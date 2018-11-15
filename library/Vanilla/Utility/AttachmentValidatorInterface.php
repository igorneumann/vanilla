<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Utility;

/**
 * An interface for classes intending to validate a media's attachment status.
 *
 * @package Vanilla\Utility
 */
interface AttachmentValidatorInterface {
    /**
     * Verify the current user can attach a media item to a specific resource.
     *
     * @param bool $canAttach Current state of whether or not a user can attach to this resource.
     * @param string $foreignType Target resource type/table (e.g. comment, discussion).
     * @param int $foreignID Unique numeric ID of the resource being attached to.
     * @return bool Whether or not the current user should be able to attach a media item to this specific resource.
     */
    public function canAttachMedia_handler(bool $canAttach, string $foreignType, int $foreignID): bool;
}
