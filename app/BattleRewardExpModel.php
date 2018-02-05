<?php

namespace App;
use DB;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;
class BattleRewardExpModel extends Model
{
	protected $fillable = ['bs_id','map_id','ranking','item_org_id','item_max_quantity','item_type','rate_from','rate_to','start_date','end_date','createdate'];

	protected $connection='mysql';
	protected $table = "Battle_reward_exp";
}