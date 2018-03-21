<?php

namespace Ndexondeck\Lauditor\Contracts;

interface AuditUser
{

    public function scopeBranch($q,$branch_id);

    public function getFullnameAttribute();

    public function getUserTypeNameAttribute();

}