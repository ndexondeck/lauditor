<?php

//Add the following events to app/Providers/EventServiceProvider.php
//inside the boot method, so as to register and handle these
//events for your application before it gets fired

//Exception Events
Event::listen('no.audit.trail', function () {
    //Should throw an exception
    //e.g
    throw new \Exception('Trail could not find any matching record.');
});

Event::listen('authorize.discards.only.pending', function () {
    //Should throw an exception
    //e.g
    throw new \Exception('Authorization status must be pending before discarding');
});

Event::listen('missing.authorization.action', function () {
    //Should throw an exception
    //e.g
    throw new \Exception('Model Authorization action is unknown, please set with Authorization::setAuthAction()');
});

Event::listen('authorize.access.denied', function () {
    //Should throw an exception
    //e.g
    throw new \Exception('Access denied due to permission');
});

Event::listen('authorize.forwards.only.pending', function () {
    //Should throw an exception
    //e.g
    throw new \Exception('Authorization status must be pending before forwarding');
});

Event::listen('authorize.approves.only.forwarded', function () {
    //Should throw an exception
    //e.g
    throw new \Exception('Cannot approve a pending request');
});

Event::listen('empty.authorization.request', function () {
    //Should throw an exception
    //e.g
    throw new \Exception('Fatal: No data was found for this authorization request');
});

Event::listen('authorize.rejects.only.forwarded', function () {
    //Should throw an exception
    //e.g
    throw new \Exception('Cannot reject a pending request');
});

Event::listen('missing.authorize.rejection.data', function () {
    //Should throw an exception
    //e.g
    throw new \Exception('You cannot reject a request without stating why.. Please enter a comment');
});

Event::listen('duplicate.auth.request', function () {
    //Should throw an exception
    //e.g
    throw new \Exception('You have already made this request please wait for authorization, or cancel request');
});

Event::listen('duplicate.forwarded.auth.request', function () {
    //Should throw an exception
    //e.g
    throw new \Exception('You have already forwarded this request please wait for feedback from the next available authorizer');
});

Event::listen('similar.auth.request', function () {
    //Should throw an exception
    //e.g
    throw new \Exception('You have already made a similar request please wait for authorization, or cancel request');
});

Event::listen('similar.forwarded.auth.request', function () {
    //Should throw an exception
    //e.g
    throw new \Exception('You have already forwarded a similar request please wait for response from the next available authorizer');
});

Event::listen('invalid.login', function ($login=null) {
    //Handle what should happen when login fails
    throw new \Exception('Login Failed');

});

Event::listen('pwd.cycle.threshold.exceeded', function ($login=null) {
    //Handle what should happen when password cycle threshold is exceeded
    throw new \Exception('Whoops! you can\'t change your password to your previously used passwords.');
});

Event::listen('duplicate.resource.auth', function () {
    //Handle what should happen
    throw new \Exception('Requested resource to be authorized may already exist, you may reject this request');

});




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

