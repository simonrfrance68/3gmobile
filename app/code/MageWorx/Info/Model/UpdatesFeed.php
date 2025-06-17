<?php
/**
 * Copyright ©  MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\Info\Model;

use MageWorx\Info\Helper\Data;

class UpdatesFeed extends AbstractFeed
{
    /**
     * @var string
     */
    const CACHE_IDENTIFIER = 'mageworx_updates_notifications_lastcheck';

    /**
     * Feed url
     *
     * @var string
     */
    protected $_feedUrl = Data::MAGEWORX_SITE . '/infoprovider/index/updates';
}
