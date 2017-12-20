<?php

namespace App\Http\Controllers;
use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\UserResourcePurchaseHistoryModel;
use App\ResourceMstModel;
use App\UserModel;
use App\UserBaggageResModel;
use App\StoreReRewardModel;
use App\StoreGemToCoinMstModel;
use App\InAppPurchaseModel;
use App\DefindMstModel;
use App\GemPurchaseBundleMst;
use Exception;
use App\Exceptions\Handler;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;
use App\Util\BaggageUtil;
use Carbon\Carbon;
use DateTime;
use App\Http\Controllers\MissionController;

class ShopController extends Controller
{
	public function shopCoin(Request $request){
		$req=$request->getContent();
		$json=base64_decode($req);
		$data=json_decode($json,TRUE);
		$now=new DateTime;
		$datetime=$now->format( 'Y-m-d h:m:s' );
		$dmy=$now->format( 'Ymd' );
		$redisShop= Redis::connection('default');
		$loginToday=$redisShop->HGET('login_data',$dmy.$data['u_id']);
		$loginTodayArr=json_decode($loginToday);
		$access_token=$loginTodayArr->access_token;
		$inAppModel=new InAppPurchaseModel();
		if($access_token==$data['access_token']){
		$resourceShop=$inAppModel->select('item_id','item_type','item_min_quantity','item_max_times','item_spend')->where('start_date','<=',$datetime)->where('end_date','>=',$datetime)->get();
		$result['shop_list']=$resourceShop;
		$response=json_encode($result,TRUE);
 	    return base64_encode($response);
 		}
 		else {
 			return base64_encode("there is something wrong with token");
 		}
	}

	public function buyResouceBYCoin(Request $request){
		$req=$request->getContent();
		$json=base64_decode($req);
		$data=json_decode($json,TRUE);
		$now=new DateTime;
		$datetime=$now->format( 'Y-m-d h:m:s' );
		$dmy=$now->format( 'Ymd' );
		$redisShop= Redis::connection('default');
		
		$UserModel=new UserModel;
		$inAppModel=new InAppPurchaseModel();
		$BaggageUtil=new BaggageUtil();


		$u_id=$data['u_id'];
		$item_id=$data['item_id'];
		$item_type=$data['item_type'];
		$times=$data['item_times'];
		$shopData=$inAppModel->select('item_spend','item_min_quantity')->where('item_id',$item_id)->where('item_type',$item_type)->where('start_date','<=',$datetime)->where('end_date','>=',$datetime)->first();
		$totalSpend=$times*$shopData['item_spend'];
		$userData=$UserModel->select('u_coin')->where('u_id',$u_id)->first();
		$loginToday=$redisShop->HGET('login_data',$dmy.$data['u_id']);
		$loginTodayArr=json_decode($loginToday);
		$access_token=$loginTodayArr->access_token;
		if($access_token==$data['access_token']){
			if($userData['u_coin']<$totalSpend){
			return base64_encode("no enough coin");
			}
			else{	
				$coin=$userData['u_coin']-$totalSpend;
				$UserModel->where('u_id',$u_id)->update(['u_coin'=>$coin,'updated_at'=>$datetime]);
				$BaggageUtil->updateBaggageResource($u_id,$item_id,$item_type,$shopData['item_min_quantity']*$times);
				$boughtData['u_id']=$u_id;
				$boughtData['item_type']=$item_type;
				$boughtData['item_id']=$item_id;
				$boughtData['item_quantity']=($shopData['item_min_quantity']*$times);
				$boughtData['spent']=$totalSpend;
				$boughtJson=json_encode($boughtData,TRUE);
				$redisShop->LPUSH('buy_resource',$boughtJson);
				$this->RecordSpend($u_id,$totalSpend,0);
				return base64_encode($boughtJson);
			}
		}
		else {
 			return base64_encode("there is something wrong with token");
 		}
	}

	public function rareResourceList (Request $request){
		$req=$request->getContent();
		$json=base64_decode($req);
		$data=json_decode($json,TRUE);
		$now=new DateTime;
		$datetime=$now->format( 'Y-m-d h:m:s' );
		$dmy=$now->format( 'Ymd' );
		$redisShop=Redis::connection('default');
		$storeReModel=new StoreReRewardModel();
		$defindMst=new DefindMstModel();
		$rate=$defindMst->where('defind_id',23)->first();
		$refresh=$defindMst->where('defind_id',25)->first();
		$refreshDuration=$defindMst->where('defind_id',26)->first();
		$loginToday=$redisShop->HGET('login_data',$dmy.$data['u_id']);
		$loginTodayArr=json_decode($loginToday);
		$access_token=$loginTodayArr->access_token;
		if($access_token==$data['access_token']&&$data){
				$u_id=$data['u_id'];
				$listCount=0;
				$times=$redisShop->HGET('refresh_times',$dmy.$u_id);
			if($times>0){
				$key='store_rare_'.$u_id.'_'.$dmy.'_'.$times;
				$listCount=$redisShop->LLEN($key);
				}
			else{
				$key='store_rare_'.$u_id.'_'.$dmy.'_0';
				$listCount=$redisShop->LLEN($key);	
				$redisShop->HSET('refresh_times',$dmy.$u_id,0);
			}
			$rewardList=[];
			$tempList=[];
			$idList=[];
		
			if($listCount>0){
				$rewardList=$redisShop->LRANGE($key,0,$listCount);
				$rewardList=array_reverse($rewardList);
				foreach($rewardList as $each){
					$tempList[]=json_decode($each,TRUE);
				}
				$result['reward']=$tempList;
				$result['times']=$times;

				if($times>0){
				$result['spend_gem']=$refresh['value2']*($times+1);
				$result['next_gem']=$refresh['value2']*($times+2);
				}
				else{
				$result['spend_gem']=$refresh['value1'];
				$result['next_gem']=$refresh['value2'];
				}

				$result['refresh_time']=strtotime(date("Y-m-d 5:0:0",strtotime("+1 day")))-time();
				$rewardJson=json_encode($result,TRUE);
				return base64_encode($rewardJson);
			}
			else{	
				for($i=1;$i<=6;$i++){
					$reward=array();
					$number=rand($rate['value1'],$rate['value2']);
					$reward=$storeReModel->select('store_reward_id','item_id','item_type','item_quantity','gem_spend')->where('rate_from','<=',$number)->where('rate_to','>=',$number)->wherenotIn('store_reward_id',$idList)->first();
					$idList[]=$reward['store_reward_id'];
					$reward['status']=0;
					$result['reward'][]=$reward;	
					$redisShop->LPUSH($key,$reward);
				}
				$result['times']=0;
				$result['spend_gem']=$refresh['value1'];
				$result['next_gem']=$refresh['value2'];
				$result['refresh_time']=strtotime(date("Y-m-d 5:0:0",strtotime("+1 day")))-time();
				$data=json_encode($result,TRUE);
				return base64_encode($data);
				}
			}
		else {
 			return base64_encode("there is something wrong with token");
 		}
	}

		public function buyFromRefreshList(Request $request){
			$req=$request->getContent();
			$json=base64_decode($req);
			$data=json_decode($json,TRUE);
			$now=new DateTime;
			$datetime=$now->format( 'Y-m-d h:m:s' );
			$dmy=$now->format( 'Ymd' );
			$position=$data['number'];
			$u_id=$data['u_id'];
			$userModel=new UserModel();
			$redisShop=Redis::connection('default');
			$BaggageUtil=new BaggageUtil();
			$times=$redisShop->HGET('refresh_times',$dmy.$u_id);
			$key='store_rare_'.$u_id.'_'.$dmy.'_'.$times;
			$listCount=$redisShop->LLEN($key);
			$rewardData=$redisShop->LRANGE($key,$listCount-$position,$listCount-$position);
			$reward=json_decode($rewardData[0],True);
			$gem_spend=$reward['gem_spend'];
			$user_gem=$userModel->select('u_gem')->where('u_id',$u_id)->first();
			$loginToday=$redisShop->HGET('login_data',$dmy.$data['u_id']);
			$loginTodayArr=json_decode($loginToday);
			$access_token=$loginTodayArr->access_token;
			if($access_token==$data['access_token']){
				if($user_gem['u_gem']<$gem_spend){
				return base64_encode("no enough gems");
				}else{
					$BaggageUtil->updateBaggageResource($u_id,$reward['item_id'],$reward['item_type'],$reward['item_quantity']);
					$user_gem=$user_gem['u_gem']-$gem_spend;
					$userModel->where('u_id',$u_id)->update(['u_gem'=>$user_gem]);
					$reward['status']=1;
					$reward=json_encode($reward,TRUE);
					$redisShop->LSET($key,$listCount-$position,$reward);
					$this->RecordSpend($u_id,0,$gem_spend);
					return base64_encode('successfully');
					}
				}
			else {
 			return base64_encode("there is something wrong with token");
 			}
			
		}

		public function refreshResource(Request $request){
			$req=$request->getContent();
			$json=base64_decode($req);
			$data=json_decode($json,TRUE);
			$now=new DateTime;
			$datetime=$now->format( 'Y-m-d h:m:s' );
			$dmy=$now->format( 'Ymd' );
			$redisShop=Redis::connection('default');
			$u_id=$data['u_id'];
			$times=$redisShop->HGET('refresh_times',$dmy.$u_id);
			$loginToday=$redisShop->HGET('login_data',$dmy.$data['u_id']);
			$loginTodayArr=json_decode($loginToday);
			$access_token=$loginTodayArr->access_token;
			if($access_token==$data['access_token']){

				if($times==5){
					return base64_encode("you only have five times chance!");
				}
				else {
					$defindMst=new DefindMstModel();
					$refresh=$defindMst->where('defind_id',25)->first();
					$spend=($times+1)*$refresh['value2'];
					$redisShop->HSET('refresh_times',$dmy.$u_id,$times+1);
					$result['times']=$times+1;
					$result['spend']=$spend;
					$response=json_encode($result,TRUE);
					return base64_encode($response);
				}
			}
			else {
 			return base64_encode("there is something wrong with token");
 			}

		}

		public function getCoinList(Request $request){
			$req=$request->getContent();
			$json=base64_decode($req);
			$data=json_decode($json,TRUE);
			$now=new DateTime;
			$datetime=$now->format( 'Y-m-d h:m:s' );
			$dmy=$now->format( 'Ymd' );
			$redisShop=Redis::connection('default');
			$loginToday=$redisShop->HGET('login_data',$dmy.$data['u_id']);
			$loginTodayArr=json_decode($loginToday);
			$access_token=$loginTodayArr->access_token;
			$u_id=$data['u_id'];
			$UserModel=new UserModel;
			
			$StoreGemToCoinMstModel=new StoreGemToCoinMstModel;
			if($access_token==$data['access_token']){
				$coinList=$StoreGemToCoinMstModel->select('id','coin','gem')->where('start_date','<=',$datetime)->where('end_date','>=',$datetime)->get();
				$result['store_coin_list']=$coinList;
				$response=json_encode($result,TRUE);
				return base64_encode($response);
			}
			else {
				return base64_encode("there is something wrong with token");
			}
		}

		public function getGemList(Request $request){
			$req=$request->getContent();
			$json=base64_decode($req);
			$data=json_decode($json,TRUE);
			$now=new DateTime;
			$datetime=$now->format( 'Y-m-d h:m:s' );
			$dmy=$now->format( 'Ymd' );
			$redisShop=Redis::connection('default');
			$loginToday=$redisShop->HGET('login_data',$dmy.$data['u_id']);
			$loginTodayArr=json_decode($loginToday);
			$access_token=$loginTodayArr->access_token;
			$u_id=$data['u_id'];
			$UserModel=new UserModel;
			$GemPurchaseBundleMst=new GemPurchaseBundleMst;
			if($access_token==$data['access_token']){
				$userData=$UserModel->select('country','os')->where('u_id',$u_id)->first();
				$gemList=$GemPurchaseBundleMst->select('bundle_id','u_payment','gem_quantity')->where('start_date','<=',$datetime)->where('end_date','>=',$datetime)->where('os',$userData['os'])->where('country',$userData['country'])->get();
				$result['store_gem_list']=$gemList;
				$response=json_encode($result,TRUE);
				return base64_encode($response);
			}
			else {
				return base64_encode("there is something wrong with token");
			}

		}

	public function buyCoin(Request $request)
	{	
		$req=$request->getContent();
		$json=base64_decode($req);
		$data=json_decode($json,TRUE);
		$now=new DateTime;
		$datetime=$now->format( 'Y-m-d h:m:s' );
		$dmy=$now->format( 'Ymd' );
		$redisShop=Redis::connection('default');
		$loginToday=$redisShop->HGET('login_data',$dmy.$data['u_id']);
		$loginTodayArr=json_decode($loginToday);
		$access_token=$loginTodayArr->access_token;
		$u_id=$data['u_id'];
		$coin=$data['coin'];
		$UserModel=new UserModel;
		$StoreGemToCoinMstModel=new StoreGemToCoinMstModel;
		$buyType=$StoreGemToCoinMstModel->where('coin',$coin)->where('start_date','<=',$datetime)->where('end_date','>=',$datetime)->first();
		$UserInfo=$UserModel->select('u_gem','u_coin')->where('u_id',$u_id)->first();
		$spend_gem=$buyType['gem'];
		$get_coin=$buyType['coin'];
		$key="store_buy_coin_".$u_id;
		if($access_token==$data['access_token']){
			if($UserInfo['u_gem']-$spend_gem>0){
				$updateGem=$UserInfo['u_gem']-$spend_gem;
				$updateCoin=$UserInfo['u_coin']+$get_coin;

				$UserModel->where('u_id',$u_id)->update(['u_gem'=>$updateGem,'u_coin'=>$updateCoin]);
				$buyData['u_id']=$u_id;
				$buyData['datetime']=time();
				$buyData['spend_gem']=$spend_gem;
				$buyData['bought_coin']=$get_coin;
				$buyData['gem_before']=$UserInfo['u_gem'];
				$buyData['coin_before']=$UserInfo['u_coin'];
				$data=json_encode($buyData,TRUE);
				$redisShop->LPUSH($key,$data);
				$this->RecordSpend($u_id,0,$spend_gem);
				return base64_encode('successfully');
				}
				else{
					return base64_encode("no enough gems");	
				}
		}
		else {
 			return base64_encode("there is something wrong with token");
 			}
 		}

 		private function RecordSpend($u_id,$coin,$gem){
 				$mission=new MissionController();
 				$now=new DateTime;
				$datetime=$now->format( 'Y-m-d h:m:s' );
				$dmy=$now->format( 'Ymd' );
 				$spentKey='daily_spend_'.$dmy;
 				$redisShop=Redis::connection('default');
				$dailySpend=$redisShop->HGET($spentKey,$u_id);
				$dailySpendData=json_decode($dailySpend,TRUE);
				$spendData['coin']=$dailySpendData['coin']+$coin;
				$spendData['gem']=$dailySpendData['coin']+$gem;
				$spendJson=json_encode($spendData,TRUE);
				$mission->archiveMission(5,$u_id,$spendData['coin']);
				$mission->archiveMission(6,$u_id,$spendData['gem']);
				$redisShop->HSET($spentKey,$u_id,$spendJson);
 		}

}