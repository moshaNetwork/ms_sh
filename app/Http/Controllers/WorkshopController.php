<?php

namespace App\Http\Controllers;
use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\UserModel;
use App\CharacterModel;
use App\EquipmentMstModel;
use App\SkillMstModel;
use App\EffectionMstModel;
use App\ImgMstModel;
use App\Util\ItemInfoUtil;
use Exception;
use App\Exceptions\Handler;
use Illuminate\Http\Response;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Redis;
class WorkshopController extends Controller
{
	public function workshop(Request $request)
	{
		$req=$request->getContent();
		$json=base64_decode($req);
		$data=json_decode($json,TRUE);

		$CharacterModel=new CharacterModel();
		$EquipmentMstModel=new EquipmentMstModel();
		$SkillMstModel=new SkillMstModel();
		$result=[];
		$weaponData=[];
		$movementData=[];
		$coreData=[];

		$u_id=$data['u_id'];
		if(isset($u_id))
		{
			$characterDetail=$CharacterModel->where('u_id',$u_id)->first();
			$characterInfo=$CharacterModel->select('ch_id','ch_title','ch_lv','ch_star','ch_hp_max','ch_atk','ch_def','ch_res','ch_crit','ch_cd','ch_spd','ch_img')->where('u_id',$u_id)->first();
			$result['Workshop_Data']['Character_info']=$characterInfo;

			$WeaponId=$characterDetail['w_id'];
			$weaponDetail=$EquipmentMstModel->select('skill_id','icon_path')->where('equ_id',$WeaponId)->first();
			$MovementId=$characterDetail['m_id'];
			$movementDetail=$EquipmentMstModel->select('skill_id','icon_path')->where('equ_id',$MovementId)->first();
			$CoreId=$characterDetail['core_id'];
			$coreDetail=$EquipmentMstModel->select('skill_id','icon_path')->where('equ_id',$CoreId)->first();
			$WeaSkillId=$weaponDetail['skill_id'];
			$weaSkillDetail=$SkillMstModel->select('skill_icon')->where('skill_id',$WeaSkillId)->first();
			$MoveSkillId=$movementDetail['skill_id'];
			$moveSkillDetail=$SkillMstModel->select('skill_icon')->where('skill_id',$MoveSkillId)->first();
			$CoreSkillId=$coreDetail['skill_id'];
			$coreSkillDetail=$SkillMstModel->select('skill_icon')->where('skill_id',$CoreSkillId)->first();

			$weaponData['equ_id']=$characterDetail['w_id'];
			$weaponData['equ_icon']=$weaponDetail['icon_path'];
			$weaponData['skill_id']=$weaponDetail['skill_id'];
			$weaponData['skill_icon']=$weaSkillDetail['skill_icon'];
			$result['Workshop_Data']['Weapon_Data']=$weaponData;

			$movementData['equ_id']=$characterDetail['m_id'];
			$movementData['equ_icon']=$movementDetail['icon_path'];
			$movementData['skill_id']=$movementDetail['skill_id'];
			$movementData['skill_icon']=$moveSkillDetail['skill_icon'];
			$result['Workshop_Data']['Movement_Data']=$movementData;

			$coreData['equ_id']=$characterDetail['core_id'];
			$coreData['equ_icon']=$coreDetail['icon_path'];
			$coreData['skill_id']=$coreDetail['skill_id'];
			$coreData['skill_icon']=$coreSkillDetail['skill_icon'];
			$result['Workshop_Data']['Core_Data']=$coreData;

			$response=json_encode($result,TRUE);
		}else
		{
			throw new Exception("there have some error of you access_token");
			$response=[
			'status' => 'Wrong',
			'error' => "please check u_id",
			];
		}
		return base64_encode($response);
	}

	public function showEquipmentInfo (Request $request)
	{
		$req=$request->getContent();
		$json=base64_decode($req);
		$data=json_decode($json,TRUE);

		$EquipmentMstModel=new EquipmentMstModel();
		$ItemInfoUtil=new ItemInfoUtil();
		$result=[];

		$Item_Id=$data['equ_id'];
		if(isset($Item_Id))
		{
			$EquipmentDetail = $ItemInfoUtil->getEquipmentInfo($Item_Id);
			$response=$EquipmentDetail;
		}else
		{
			throw new Exception("Wrong Equipment ID data");
			$response=[
			'status' => 'Wrong',
			'error' => "please check Equipment ID data",
			];
		}
		return base64_encode($response);
	}

	public function showSkillInfo (Request $request)
	{
		$req=$request->getContent();
		$json=base64_decode($req);
		$data=json_decode($json,TRUE);

		$SkillMstModel=new SkillMstModel();
		$ItemInfoUtil=new ItemInfoUtil();
		$result=[];

		$skill_id=$data['skill_id'];
		if(isset($skill_id))
		{
			$SkillDetail = $ItemInfoUtil->getSkillInfo($skill_id);
			$response=$SkillDetail;
		}else
		{
			throw new Exception("Wrong Skill ID data");
			$response=[
			'status' => 'Wrong',
			'error' => "please check Skill ID data",
			];
		}
		return base64_encode($response);
	}

	//compare two equipments in the workshop. show the details of equipments and the skills.
	public function compareEquipment (Request $request)
	{
		$req=$request->getContent();
		$data=json_decode($req,TRUE);

		$EquipmentMstModel=new EquipmentMstModel();
		$CharacterModel=new CharacterModel();
		$SkillMstModel=new SkillMstModel();
		$ItemInfoUtil=new ItemInfoUtil();
		$result=[];

		$u_id=$data['u_id'];
		$equ_id=$data['equ_id'];

		if(isset($u_id))
		{
			$Equ_click_detail=$ItemInfoUtil->getEquipmentInfo($equ_id);
			$Skill_click_id=$EquipmentMstModel->where('equ_id',$equ_id)->pluck('skill_id');
			$Skill_click_detail=$ItemInfoUtil->getSkillInfo($Skill_click_id);
			$result['equ_click_data']=$Equ_click_detail;
			$result['equ_click_data']=$Skill_click_detail;

			$Equ=$EquipmentMstModel->where('equ_id',$equ_id)->first();
			$Equ_part=$Equ['equ_part'];

			if($Equ_part == 1)
			{
				$Equ_now_id=$CharacterModel->where('u_id',$u_id)->pluck('w_id');
				$Equ_now_detail=$ItemInfoUtil->getEquipmentInfo($Equ_now_id);
				$Skill_now_id=$EquipmentMstModel->where('equ_id',$Equ_now_id)->pluck('skill_id');
				$Skill_now_detail=$ItemInfoUtil->getSkillInfo($Skill_now_id);
				$result['equ_now_data']=$Equ_now_detail;
				$result['equ_now_data']=$Skill_now_detail;
			}else if($Equ_part == 2)
			{
				$Equ_now_id=$CharacterModel->where('u_id',$u_id)->pluck('m_id');
				$Equ_now_detail=$ItemInfoUtil->getEquipmentInfo($Equ_now_id);
				$Skill_now_id=$EquipmentMstModel->where('equ_id',$Equ_now_id)->pluck('skill_id');
				$Skill_now_detail=$ItemInfoUtil->getSkillInfo($Skill_now_id);
				$result['equ_now_data']=$Equ_now_detail;
				$result['equ_now_data']=$Skill_now_detail;
			}else if($Equ_part == 3)
			{
				$Equ_now_id=$CharacterModel->where('u_id',$u_id)->pluck('core_id');
				$Equ_now_detail=$ItemInfoUtil->getEquipmentInfo($Equ_now_id);
				$Skill_now_id=$EquipmentMstModel->where('equ_id',$Equ_now_id)->pluck('skill_id');
				$Skill_now_detail=$ItemInfoUtil->getSkillInfo($Skill_now_id);
				$result['equ_now_data']=$Equ_now_detail;
				$result['equ_now_data']=$Skill_now_detail;
			}
			$response=json_encode($result,TRUE);			
		}else{
			throw new Exception("there have some error of you access_token");
			$response=[
			'status' => 'Wrong',
			'error' => "please check u_id",
			];
		}
		return $response;
	}

	//after user change equipment, adjust the attributes of chararcter and change the character image
	public function equipEquipment (Request $request)
	{
		$req=$request->getContent();
		$data=json_decode($req,TRUE);
		$now=new DateTime;
		$datetime=$now->format( 'Y-m-d h:m:s' );
		$dmy=$now->format( 'Ymd' );
		
		$CharacterModel=new CharacterModel();
		$EquipmentMstModel=new EquipmentMstModel();
		$ImgMstModel=new ImgMstModel();
		$result=[];

		$u_id=$data['u_id'];
		$equ_id=$data['equ_id'];

		if(isset($u_id))
		{
			$characterDetail=$CharacterModel->where('u_id',$u_id)->first();
			$w_id=$characterDetail['w_id'];
			$m_id=$characterDetail['m_id'];
			$core_id=$characterDetail['core_id'];
			$hp=$characterDetail['ch_hp_max'];
			$atk=$characterDetail['ch_atk'];
			$def=$characterDetail['ch_def'];
			$crit=$characterDetail['ch_crit'];
			$cd=$characterDetail['ch_cd'];

			$EquNew=$EquipmentMstModel->where('equ_id',$equ_id)->first();
			$Equ_part=$EquNew['equ_part'];

			if($Equ_part == 1)
			{
				$EquOld=$EquipmentMstModel->where('equ_id',$w_id)->first();
				$effOld_id=$EquOld['eff_id'];
				$effOld=$EffectionMstModel->where('eff_id',$effOld_id)->first();

				$effNew_id=$EquNew['eff_id'];
				$effNew=$EffectionMstModel->where('eff_id',$effNew_id)->first();

				$hp_old=$effOld['eff_ch_hp_max'];
				$hp_new=$effNew['eff_ch_hp_max'];
				$hp_updated=$hp-$hp_old+$hp_new;

				$atk_old=$effOld['eff_ch_atk'];
				$atk_new=$effNew['eff_ch_atk'];
				$atk_updated=$atk-$atk_old+$atk_new;

				$def_old=$effOld['eff_ch_def'];
				$def_new=$effNew['eff_ch_def'];
				$def_updated=$def-$def_old+$def_new;

				$crit_old=$effOld['eff_ch_crit_per'];
				$crit_new=$effNew['eff_ch_crit_per'];
				$crit_updated=$crit-$crit_old+$crit_new;

				$cd_old=$effOld['eff_ch_cd'];
				$cd_new=$effNew['eff_ch_cd'];
				$cd_updated=$cd-$cd_old+$cd_new;

				$img=$ImgMstModel->where('w_id',$equ_id)->where('m_id',$m_id)->where('core_id',$core_id)->first();

				$CharacterModel->where('u_id',$u_id)->update(['w_id'=>$equ_id,'ch_hp_max'=>$hp_updated,'ch_atk'=>$atk_updated,'ch_def'=>$def_updated,'ch_crit'=>$crit_updated,'ch_cd'=>$cd_updated,'ch_img'=>$img['img_id']]);

				$response='Weapon changed';
			}else if($Equ_part == 2)
			{
				$EquOld=$EquipmentMstModel->where('equ_id',$m_id)->first();
				$effOld_id=$EquOld['eff_id'];
				$effOld=$EffectionMstModel->where('eff_id',$effOld_id)->first();

				$effNew_id=$EquNew['eff_id'];
				$effNew=$EffectionMstModel->where('eff_id',$effNew_id)->first();

				$hp_old=$effOld['eff_ch_hp_max'];
				$hp_new=$effNew['eff_ch_hp_max'];
				$hp_updated=$hp-$hp_old+$hp_new;

				$atk_old=$effOld['eff_ch_atk'];
				$atk_new=$effNew['eff_ch_atk'];
				$atk_updated=$atk-$atk_old+$atk_new;

				$def_old=$effOld['eff_ch_def'];
				$def_new=$effNew['eff_ch_def'];
				$def_updated=$def-$def_old+$def_new;

				$crit_old=$effOld['eff_ch_crit_per'];
				$crit_new=$effNew['eff_ch_crit_per'];
				$crit_updated=$crit-$crit_old+$crit_new;

				$cd_old=$effOld['eff_ch_cd'];
				$cd_new=$effNew['eff_ch_cd'];
				$cd_updated=$cd-$cd_old+$cd_new;

				$img=$ImgMstModel->where('w_id',$w_id)->where('m_id',$equ_id)->where('core_id',$core_id)->first();

				$CharacterModel->where('u_id',$u_id)->update(['m_id'=>$equ_id,'ch_hp_max'=>$hp_updated,'ch_atk'=>$atk_updated,'ch_def'=>$def_updated,'ch_crit'=>$crit_updated,'ch_cd'=>$cd_updated,'ch_img'=>$img['img_id']]);

				$response='Movement changed';
			}else if($Equ_part == 3)
			{
				$EquOld=$EquipmentMstModel->where('equ_id',$core_id)->first();
				$effOld_id=$EquOld['eff_id'];
				$effOld=$EffectionMstModel->where('eff_id',$effOld_id)->first();

				$effNew_id=$EquNew['eff_id'];
				$effNew=$EffectionMstModel->where('eff_id',$effNew_id)->first();

				$hp_old=$effOld['eff_ch_hp_max'];
				$hp_new=$effNew['eff_ch_hp_max'];
				$hp_updated=$hp-$hp_old+$hp_new;

				$atk_old=$effOld['eff_ch_atk'];
				$atk_new=$effNew['eff_ch_atk'];
				$atk_updated=$atk-$atk_old+$atk_new;

				$def_old=$effOld['eff_ch_def'];
				$def_new=$effNew['eff_ch_def'];
				$def_updated=$def-$def_old+$def_new;

				$crit_old=$effOld['eff_ch_crit_per'];
				$crit_new=$effNew['eff_ch_crit_per'];
				$crit_updated=$crit-$crit_old+$crit_new;

				$cd_old=$effOld['eff_ch_cd'];
				$cd_new=$effNew['eff_ch_cd'];
				$cd_updated=$cd-$cd_old+$cd_new;

				$img=$ImgMstModel->where('w_id',$w_id)->where('m_id',$m_id)->where('core_id',$equ_id)->first();

				$CharacterModel->where('u_id',$u_id)->update(['core_id'=>$equ_id,'ch_hp_max'=>$hp_updated,'ch_atk'=>$atk_updated,'ch_def'=>$def_updated,'ch_crit'=>$crit_updated,'ch_cd'=>$cd_updated,'ch_img'=>$img['img_id']]);

				$response='Core changed';
			}else{
				throw new Exception("Change equipment error");
				$response=[
				'status' => 'Wrong',
				'error' => "please check workshop controller",
				];
			}
		}else{
			throw new Exception("there have some error of you access_token");
			$response=[
			'status' => 'Wrong',
			'error' => "please check u_id",
			];
		}
		return $response;
	}
}