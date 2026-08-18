<?php
namespace App\Models;use Illuminate\Database\Eloquent\Concerns\HasUuids;use Illuminate\Database\Eloquent\Model;final class ConnectorSecret extends Model{use HasUuids;protected $guarded=[];protected $hidden=['secret_encrypted'];protected function casts():array{return ['secret_encrypted'=>'encrypted'];}}
