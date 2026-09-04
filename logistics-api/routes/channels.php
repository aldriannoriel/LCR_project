<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('hub.{hubId}', fn ($user, $hubId) => $user->hub_id === null || (int) $user->hub_id === (int) $hubId);