<?php

return[
    //Success
    'success'=>['00','Ok'],

    //Login Error Codes
    'missing_username_and_password'=>['1000','Missing username and password'],
    'missing_username'=>['1001','Missing username'],
    'missing_password'=>['1002','Missing password'],
    'invalid_login'=>['1003','Login failed'],
    'invalid_username'=>['1004','Invalid username'],
    'blocked_account'=>['1005','Account has been blocked. Please use the forget password link to recover your account.'],
    'disabled_account'=>['1006','Account has been disabled. Please send us a mail to restore your account.'],
    'missing_new_password'=>['1007','Missing new password'],
    'missing_old_password'=>['1008','Missing old password'],
    'duplicate_passwords'=>['1009','Whoops! Old password and new password cannot be the same'],
    'invalid_old_password'=>['1010','Old password is not correct'],
    'missing_secret_question'=>['1011','Please update your secret question to enhance security checks'],
    'invalid_secret_question_answer'=>['1012','The answer is not correct!'],
    'invalid_password'=>['1013','Invalid password'],
    'except_me'=>['1014','This request excludes you from its scope'],
    'not_on_duty' => ['1015', "Whoop! You can't login at the moment. Please wait until it's your working hour."],
    'is_weekend' => ['1016', "Oops! it's a weekend. You can't access the system today."],

    //Session Error Codes
    'invalid_account'=>['1100','User account does not exist or is invalid!'],
    'missing_account'=>['1101','Unknown user, please specify user login in request!'],

    //Permission Error
    'invalid_actor'=>['1200','The specified user cannot access this resource'],
    'invalid_staff'=>['1201','The specified user is not a valid staff'],
    'access_denied'=>['1202','Access denied due to permission'],
    'group_access_denied'=>['1203','Access denied - Group is disabled'],


    //Dependency Error
    'no_auth'=>['1300','The user request must be authenticated first'],
    'no_staff_auth'=>['1301','The staff request must be authenticated first'],
    'authorize_discards_only_pending'=>['1302','Authorization status must be pending before discarding'],
    'authorize_forwards_only_pending'=>['1303','Authorization status must be pending before forwarding'],
    'authorize_approves_only_forwarded'=>['1304','Cannot approve a pending request'],
    'authorize_rejects_only_forwarded'=>['1305','Cannot reject a pending request'],
    'audit_restores_previous'=>['1306','You cannot restore this commit, without restoring the older one first.'],
    'no_audit_trail'=>['1307','Trail could not find any matching record.'],

    //Validation Error
    'validation_failure'=>['1400','Validation Error!, please send in correct form values'],
    'missing_authorization_action'=>['1401','Model Authorization action is unknown, please set with Authorization::setAuthAction()'],
    'duplicate_auth_request'=>['1402','You have already made this request please wait for authorization, or cancel request'],
    'duplicate_forwarded_auth_request'=>['1403','You have already forwarded this request please wait for response from the next available authorizer'],
    'missing_authorize_rejection_data'=>['1404','You cannot reject a request without stating why.. Please enter a comment'],
    'checked_authorizations_only' => ['1407','Checked authorization can only be of type approved or rejected'],
    'similar_auth_request'=>['1408','You have already made a similar request please wait for authorization, or cancel request'],
    'similar_forwarded_auth_request'=>['1409','You have already forwarded a similar request please wait for response from the next available authorizer'],
    'pw_cyc_threshold'=>['1420', "Whoops! you can't change your password to your previously used passwords."],

    //Signup verification Error
    'invalid_verification_hash'=>['1500','The verification hash is not valid'],
    'expired_verification_hash'=>['1501','The verification hash has expired, please re-initiate the process.'],

    //Fatal Error
    'empty_authorization_request'=>['1600','Fatal: No data was found for this authorization request'],
    'rebuild_indices'=>['1601','Fatal: There may be an inconsistent index please rebuild indices'],
    'empty_setting'=>['1603','Fatal: No setting was found for this api'],
    'invalid_index'=>['1604','Fatal: This setting index does not exist, rebuild index if you just added it'],

    //Database Error
    'duplicate_resource_auth'=>['1700','Requested resource to be authorized may already exist, you may reject this request'],
    'operation_failed'=>['1701','Whoop! Operation Failed.'],
    'record_dependency'=>['1703','You are not permitted to perform this operation'],

    'unknown'=>['99','Unknown error']

];