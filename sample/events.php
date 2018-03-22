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

Event::listen('login.changed', function ($login) {
    //Handle what should happen when login changes
});

Event::listen('send.email', function ($user,$subject,$payload,$view="emails.blank",$delay=null) {
    //Handle what should happen when a send email event is fired
});

Event::listen('user.created', function ($user) {
    //Handle what should happen when a user is created
});

Event::listen('staff.details.changed', function ($staff) {
    //Handle what should happen when a staff record is updated
});

