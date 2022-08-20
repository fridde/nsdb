<?php
	

namespace App\Entity;


trait DefaultSerializable
{
    // TODO: Remove this class. If necessary, replace with attributes

    public function getStandardMembers(): array
    {
        $return = [];

        $standardMembers = $this->standardMembers ?? [];

        foreach($standardMembers as $member){
            $return[$member] = $this->{$member};
        }
        return $return;
    }
}