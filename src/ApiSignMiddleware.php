<?php

namespace Wengg\WebmanApiSign;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class ApiSignMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        // 默认路由 $request->route 为null，所以需要判断 $request->route 是否为空
        $route = $request->route;

        // 获取控制器信息
        $class = new \ReflectionClass($request->controller);
        $properties = $class->getDefaultProperties();
        $noNeedSign = array_map('strtolower', $properties['noNeedSign'] ?? []);
        $ControlNotSign = !(in_array(strtolower($request->action), $noNeedSign) || in_array('*', $noNeedSign));
        $routeNotSign = $route && $route->param('notSign') !== null ? $route->param('notSign') : false;
        if ($ControlNotSign && $routeNotSign) {
            $service = new ApiSignService;
            $config = $service->getConfig();
            if (!$config) {
                return $next($request);
            }
            $fields = $config['fields'];
            $data = array_merge($request->all(), [
                $fields['app_key'] => $request->header($fields['app_key'], $request->input($fields['app_key'])),
                $fields['timestamp'] => $request->header($fields['timestamp'], $request->input($fields['timestamp'])),
                $fields['noncestr'] => $request->header($fields['noncestr'], $request->input($fields['noncestr'])),
                $fields['signature'] => $request->header($fields['signature'], $request->input($fields['signature'])),
            ]);
            $service->check($data);
        }
        return $next($request);
    }
}
