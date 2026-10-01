<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentationModel extends Model
{
    protected $table = 'documentation';
    protected $primaryKey = 'id';
    protected $allowedFields = ['title', 'content', 'project_id', 'category', 'created_at', 'updated_at'];
    protected $useTimestamps = false;
}