<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $appends = ['created_on'];

    public function requester(){
        return $this->belongsTo(User::class, 'user_id')->withoutGlobalScopes(['active']);
    }

    public function agent(){
        return $this->belongsTo(User::class, 'agent_id')->withoutGlobalScopes(['active']);
    }

    public function reply()
    {
        return $this->hasMany(TicketReply::class, 'ticket_id');
    }

    public function tags(){
        return $this->hasMany(TicketTag::class, 'ticket_id');
    }

    public function getCreatedOnAttribute(){
        if(!is_null($this->created_at)){
            return $this->created_at->format('d M Y H:i');
        }
        return "";
    }
}
