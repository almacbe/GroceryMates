<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('merge-state', static fn () => true);
Broadcast::channel('merge-checklist', static fn () => true);

