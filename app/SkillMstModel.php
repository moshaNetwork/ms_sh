<?php

namespace App;
use DB;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;
class SkillMstModel extends Model
{
	protected $fillable = ['skill_id','skill_name','skill_icon','skill_chartlet','self_eff_id','enemy_eff_id','skill_info','skill_id_req','createdate'];

	protected $connection='mysql';
	protected $table = "Skill_mst";
}