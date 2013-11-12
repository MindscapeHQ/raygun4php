<?php
/**
 * Created by PhpStorm.
 * User: steve
 * Date: 12/11/13
 * Time: 09:42
 */

namespace Raygun4php\Senders;

use Raygun4php\RaygunMessage;

interface RaygunMessageSender {
    public function Send(RaygunMessage $message);
} 