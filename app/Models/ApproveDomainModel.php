<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ApproveDomainModel extends Model
{
    use HasFactory;
    protected $table = 'approve_domain';
    /**
     * @var array
     */
    protected $fillable = [
        'domain_name',
        'email_domain',
        'valid_days',
        'security',
        'status',
        'used_at',
        'status_security',
        'active_page',
        'created_active',
        'OTP',
        'created_at',
        'updated_at'
    ];
}
