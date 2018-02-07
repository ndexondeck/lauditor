
<center>
    <table width='100%' style='border: 1px solid #444' cellpadding='5'>
        <tr>
            <th style='background-color:#000000;'>
                <h2 style='margin: 0;color:#fff'>First PAY {{ucwords($type)}} Job Queue</h2>
            </th>
        </tr>
        <tr>
            <td>
                <h3>Showing First <form method='get' action="{{url("queues")}}" style="display: inline">
                        <input type="hidden" name="page" value="{{$page}}" />
                        <input type="hidden" name="queue" value="{{$queue}}" />
                        <input style="width: 30px" name="paginate" value="{{$paginate}}"/></form> Job Queues</h3>
                @include('partials.paginate',['paginator'=>$results])
                <form method='get' action="{{url("queues")}}" style="float:right;padding: 10px">
                    <input type="hidden" name="page" value="{{$page}}" />
                    <input type="hidden" name="paginate" value="{{$paginate}}" />
                    <select onchange="this.parentNode.submit()" style="" name="queue">
                        {!! options([
                            'default'=>'Default',
                            'low'=>'Low Priority',
                            'high'=>'High Priority',
                        ],$queue,false) !!}
                    </select>
                </form>
                <?php $iType = ($type == "delayed")?"reserved":"delayed";?>
                <a href="{{action('Api\QueueController@index')}}?queue={{$queue."&paginate=".$paginate}}" style="float:right;padding: 10px">Queued Jobs</a>
                <a href="{{action('Api\QueueController@lists',$iType)}}?queue={{$queue."&paginate=".$paginate}}" style="float:right;padding: 10px">{{ucwords($iType)}} Jobs</a>

            </td>
        </tr>
    </table>
    <table border=1 width='100%' cellpadding='2'>
        <tr>
            <th>S/N</th>
            <th>Job</th>
            <th>Handler</th>
            <th>Process ID</th>
            <th>Attempts</th>
            <th>Delay</th>
        </tr>

        @forelse($results->toArray()['data'] as $k=>$result)
            <?php
                $result= json_decode($result,true);
                if(isset($result['data']['command'])){
                    try{
                        $v = unserialize($result['data']['command']);
                    }catch (\Exception $e){
                        $v = "Unknown";
                    }
                }
                else{
                    $v = $result['data'][0];
                }
            ?>
            <tr style="{{'background: '. (isset($scale[$result['attempts']])?$scale[$result['attempts']]:"#d44")}}">
                <td>{{$l = ((($page - 1) * $paginate) + 1 + $k)}}</td>
                <td>{{ class_basename($v) }}</td>
                <td>{{$result['job']}}</td>
                <td>{{$result['id']}}</td>
                <td>{{$result['attempts']}}</td>
                <td>{{isset($v->delay)?$v->delay:0}} Second(s)</td>
            </tr>
        @empty
            <h3>No Result found</h3>
        @endforelse

    </table>
</center>