<?php return array (
  'Illuminate\\Foundation\\Support\\Providers\\EventServiceProvider' => 
  array (
    'App\\Events\\SosTriggeredEvent' => 
    array (
      0 => 'App\\Listeners\\BroadcastSosAlert@handle',
      1 => 'App\\Listeners\\SendSosChatMessage@handle',
      2 => 'App\\Listeners\\SendSosDatabaseNotification@handle',
    ),
    'App\\Core\\UnifiedSystemEvent' => 
    array (
      0 => 'App\\Listeners\\SystemEventProcessor@handle',
    ),
  ),
);