@extends('layouts.app')
@section('content')
<div class="container">
<script type="text/javascript">
var wsl= 'ws://116.62.127.102:6385'
ws = new WebSocket(wsl);//新建立一个连接
//如下指定事件处理 
ws.onmessage = function(evt){console.log(evt.data);/*ws.close();*/};  
ws.onclose = function(evt){console.log('WebSocketClosed!');};  
ws.onerror = function(evt){console.log('WebSocketError!');}; 

</script>
<div class="form-group">  
           <label class="col-lg-4 control-label">{{trans('message.welcome')}}</label>  
            <div class="col-lg-6">  
                <input type="text" class="form-control" name="name" value="{{old('name')}}" autofocus/>  
            </div>  
       </div> 
</div>
@endsection
