<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = ['name_en', 'name_ar', 'gtin_code', 'code', 'material_type_id', 'material_group_id', 'sync_at', 'attp_required'];

    public function materialType()
    {
        return $this->belongsTo(MaterialType::class);
    }

    public function materialGroup()
    {
        return $this->belongsTo(MaterialGroup::class);
    }

    public function areas()
    {
        return $this->belongsToMany(PlantArea::class, 'material_area');
    }

    public function draftInbounds()
    {
        return $this->belongsToMany(DraftInbound::class, 'draft_inbound_material');
    }
}
