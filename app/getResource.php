<?php

function getResource($baggage_u_id)
{
	if(isset($baggage_u_id))
		{
			$UserBaggageResModel=new UserBaggageResModel();
			$result=[];

			$baggageResource=$UserBaggageResModel->where('u_id','=',$baggage_u_id)->where('status','=',0)->get();
			$result['Baggage_data']['Baggage_resource']=$baggageResource;

			$response=json_encode($result,TRUE);
		}else{
			throw new Exception("No User ID");
			$response=[
			'status' => 'Wrong',
			'error' => "please check u_id",
			];
		}
		return $response;
}