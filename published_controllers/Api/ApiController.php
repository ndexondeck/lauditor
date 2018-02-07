<?php

//namespace App\Http\Controllers\Api;

use App\LogWebService;
use App\Ndexondeck\Lauditor\Util;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

class ApiController extends Controller
{

    function index() {
        //return view('welcome');

        echo "<center>
        <table width='100%' style='border: 1px solid #444' cellpadding='5'>
                <tr>
                      <th style='background-color:#000000;'><h2 style='margin: 0;color:#fff'>".env('APP_NAME')." API Documentation</h2></th>
                </tr>";

        $object = Route::getRoutes();

        $pattern = ["/destroy/", "/index/", "/store/", "/(^me)/"];
        $replacement = ["delete", "list", "create", "my"];

        $i = 1;
        $j = 0;
        foreach ($object as $value) {

            $route = $value->getName();

            if (!$route)
                continue;

            $method = $value->methods[0];
            if (!$method == "PATCH")
                continue;

            $controller = explode("@", $value->getActionName());

            $controller_name = $controller[0];
            $controller_method = $controller[1];

            try{
                if (!in_array($controller_method, get_class_methods($controller_name)))
                    continue;
            }
            catch(\Exception $e){
                die($controller_name." does not exist. on line ". $e->getLine());
            }

            $name = str_replace("App\\Http\\Controllers\\", "", $controller_name);
            $names = explode("\\", $name);

            if (count($names) > 1) {

                if (!isset($module[$names[0]])) {
                    $i = 1;
                    echo "</table><br/><br/>
                    <h3>$names[0] Module</h3>
                    <table style='border: 1px solid #444' cellpadding='5'>
                            <tr>
                                  <th>S/N</th>
                                  <th>Task</th>
                                  <th>API Name</th>
                                  <th>HTTP Method</th>
                                  <th>Laravel Method Name</th>
                                  <th>Route</th>
                            </tr>";

                    $module[$names[0]] = true;
                }

                $names[0] = $names[1];
            }

            $name = ucwords(preg_replace($pattern, $replacement, $controller_method) . " " . str_replace("Controller", "", $names[0]));

            $color = is_int($i / 2) ? "#fff" : "#eee";

            $j++;

            echo "<tr style='background-color: $color'>";
            echo "<td>".$j."</td>";
            echo "<td>".Util::normalCase($name)."</td>";
            echo "<td>".$value->uri."</td>";
            echo "<td>".$method."</td>";
            echo "<td>". $controller_method."</td>";
            echo "<td>".$route."</td>";
            echo "</tr>";

            $i++;
        }

        echo "</table>
        <h3>Total API = $j</h3>
        </center>";
    }

    function lists() {
        //return view('welcome');

        echo "<center>
        <table width='100%' style='border: 1px solid #444' cellpadding='5'>
                <tr>
                      <th style='background-color:#000000;'><h2 style='margin: 0;color:#fff'>".env('APP_NAME')." API Documentation</h2></th>
                </tr>";

        $object = Route::getRoutes();

        $pattern = ["/destroy/", "/index/", "/store/", "/(^me)/"];
        $replacement = ["delete", "list", "create", "my"];

        $i = 1;
        $j = 0;
        foreach ($object as $value) {

            $route = $value->getName();
            if (!$route)
                continue;

            $method = $value->methods[0];
            if (!$method == "PATCH")
                continue;

            $controller = explode("@", $value->getActionName());

            $controller_name = $controller[0];
            $controller_method = $controller[1];

            try{
                if (!in_array($controller_method, get_class_methods($controller_name)))
                    continue;
            }
            catch(\Exception $e){
                die($controller_name." was not configured correctly: ". $e->getMessage() ." on line ". $e->getLine());
            }

            $name = str_replace("App\\Http\\Controllers\\", "", $controller_name);
            $names = explode("\\", $name);

            if (count($names) > 1) {

                if (!isset($module[$names[0]])) {
                    $i = 1;
                    echo "</table><br/><br/>
                    <h3>$names[0] Module</h3>
                    <table style='border: 1px solid #444' cellpadding='5'>
                            <tr>
                                  <th>S/N</th>
                                  <th>URI</th>
                                  <th>HTTP Method</th>
                                  <th>Task</th>
                            </tr>";
                    $module[$names[0]] = true;
                }

                $names[0] = $names[1];
            }

            $name = ucwords(preg_replace($pattern, $replacement, $controller_method) . " " . str_replace("Controller", "", $names[0]));

            $color = is_int($i / 2) ? "#fff" : "#eee";

            $j++;

            echo "<tr style='background-color: $color'>";
            echo "<td>".$j."</td>";
            echo "<td>".$value->uri."</td>";
            echo "<td>".$method."</td>";
            echo "<td>".Util::normalCase($name)."</td>";
            echo "</tr>";

            $i++;
        }

        echo "</table>
        <h3>Total API = $j</h3>
        </center>";
    }
}
