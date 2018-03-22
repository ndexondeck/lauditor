<?php

//Add the following events to app/Providers/EventServiceProvider.php
//inside the boot method, so as to register and handle these
//events for your application before it gets fired

//Other Events
Event::listen('on.screen.notification', function ($model,$login_id=null) {
    //Should push notification to user if possible
});

Event::listen('on.forwarding.authorization', function ($model) {
    //Handle what should happen a authorization is forwarded
});