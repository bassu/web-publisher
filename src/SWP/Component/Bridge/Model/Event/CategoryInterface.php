<?php

/*
 * This file is part of the Superdesk Web Publisher Bridge Component.
 *
 * Copyright 2016 Sourcefabric z.ú. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2016 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Component\Bridge\Model\Event;

use SWP\Component\Storage\Model\PersistableInterface;

/**
 * Interface EventInterface.
 */
interface CategoryInterface extends PersistableInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getQcode();

    /**
     * @param string $qcode
     */
    public function setQcode($qcode);

    /**
     * @return string
     */
    public function getSubject();

    /**
     * @param string $subject
     */
    public function setSubject($subject);

    /**
     * @return Event
     */
    public function getEvent();

    /**
     * @param Event $event
     */
    public function setEvent($event);
}
