
<center>
    <table width='100%' style='border: 1px solid #444' cellpadding='5'>
        <tr>
            <th style='background-color:#000000;'>
                <h2 style='margin: 0;color:#fff'>First PAY Failed Jobs Log</h2>
            </th>
        </tr>
        <tr>
            <td>
                <h3>Showing  <form method='post' action="{{url("web_services?page=$page".(($q)?"q=$q":""))}}" style="display: inline"><input style="width: 30px" name="paginate" value="{{$paginate}}"/></form></h3>
                @include('partials.paginate',['paginator'=>$failed_jobs])
                <form method='post' action="{{url("web_services?page=$page&paginate=$paginate")}}" style="float:right;padding: 10px"><input style="" name="q" placeholder="Search"/></form>


                <a href="{{action('Api\QueueController@index')}}?queue=default{{"&paginate=".$paginate}}" style="float:right;padding: 10px">Queued Jobs</a>

            </td>
        </tr>
    </table>
    <table border=1 width='100%' cellpadding='2'>
        <tr>
            <th>S/N</th>
            <th>ID</th>
            <th>Connection</th>
            <th>Queue</th>
            <th>Job</th>
            <th>Attempts</th>
            <th>Failed At</th>
        </tr>



        @forelse($failed_jobs->toArray()['data'] as $k=>$result)
            <?php
                $v = json_decode($result['payload']);
                $data = $v->data;
                if(is_array($data)) $baseName = $v->job. " ($data[0])";
                else{
                    try{
                        $baseName = class_basename(unserialize($data->command));
                    }catch (\Exception $e){
                        $baseName = "Deleted Resource";
                    }
                }
            ?>
            <tr style="background:#faa">
                <td>{{((($page - 1) * $paginate) + 1 + $k)}}</td>
                <td>{{$result['id']}}</td>
                <td>{{$result['connection']}}</td>
                <td>{{$result['queue']}}</td>
                <td>{{$baseName}}</td>
                <td>{{isset($v->attempts)?$v->attempts:1}}</td>
                <td>{{$result['failed_at']}}</td>
            </tr>
        @empty
            <h3>No Result found</h3>
        @endforelse

    </table>
</center>