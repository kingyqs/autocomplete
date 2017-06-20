<?php

/**
 * class swoole_websocket_server
 *
 * @since 3.1.0
 *
 * @package swoole_websocket_server
 */

/**
 * class swoole_websocket_server extends swoole_http_server
 * 继承自 swoole_http_server 类
 *
 * @since 3.1.0
 */
class swoole_websocket_server /*extends \swoole_http_server*/
{
    /**
     * @var int $master_pid 前服务主进程 master 的 PID
     */
    public $master_pid = 0;

    /**
     * @var int $manager_pid 当前服务管理进程 manager 的 PID
     */
    public $manager_pid = 0;

    /**
     * @var int 当前 Worker 进程的操作系统进程ID。与posix_getpid()的返回值相同。
     */
    public $worker_pid = 0;

    /**
     * @var bool $taskworker true表示当前进程是Task工作进程，false表示当前进程是Worker进程
     */
    public $taskworker;

    /**
     * @var string
     * @internal
     */
    public $pid = "";

    /**
     * @var string
     * @internal
     */
    public $id = 0;

    /**
     * @var string
     * @internal
     */
    public $host = "";

    /**
     * @var string
     * @internal
     */
    public $port = -1;

    /**
     * @var string
     * @internal
     */
    public $type = 0;

    /**
     * @var string
     * @internal
     */
    public $mode = 0;

    /**
     * @var string
     * @internal
     */
    public $ports;

    /**
     * @internal
     */
    public $connections;

    /**
     * swoole_server::set() 函数所设置的参数会保存到 swoole_server::$setting 属性上。在回调函数中可以访问运行参数的值。
     * @var array 数组，保存通过 set() 设置的参数属性列表
     */
    public $setting;

    /**
     * @var callable
     * @internal
     */
    public $onClose;

    /**
     * @var callable
     * @internal
     */
    public $onStart;

    /**
     * @var callable
     * @internal
     */
    public $onShutdown;

    /**
     * @var callable
     * @internal
     */
    public $onWorkerStart;

    /**
     * @var callable
     * @internal
     */
    public $onWorkerStop;

    /**
     * @var callable
     * @internal
     */
    public $onTask;

    /**
     * @var callable
     * @internal
     */
    public $onFinish;

    /**
     * @var callable
     * @internal
     */
    public $onWorkerError;

    /**
     * @var callable
     * @internal
     */
    public $onManagerStart;

    /**
     * @var callable
     * @internal
     */
    public $onManagerStop;

    /**
     * @var callable
     * @internal
     */
    public $onPipeMessage;

    /**
     * 当服务器收到来自客户端的数据帧时会回调此函数。函数原型：
     *
     * function onMessage(swoole_server $server, swoole_websocket_frame $frame);
     *      $frame 是swoole_websocket_frame对象，包含了客户端发来的数据帧信息；
     *      onMessage回调必须被设置，未设置服务器将无法启动；
     *      客户端发送的ping帧不会触发onMessage，底层会自动回复pong包；
     *
     * @var callable websocket server 收到客户端数据帧时回调函数
     * @internal
     */
    public $onMessage;

    /**
     * @var callable
     * @internal
     */
    public $onRequest;

    /**
     * 当WebSocket客户端与服务器建立连接并完成握手后会回调此函数。函数原型：
     *
     * function onOpen(swoole_websocket_server $svr, swoole_http_request $req);
     *      $req 是一个Http请求对象，包含了客户端发来的握手请求信息；
     *      onOpen事件函数中可以调用push向客户端发送数据或者调用close关闭连接；
     *      onOpen事件回调是可选的；
     *
     * @var callable websocket 客户端与服务端完成握手后回调函数
     * @internal
     */
    public $onOpen;

    /**
     * WebSocket 建立连接后进行握手。WebSocket 服务器已经内置了handshake，如果用户希望自己进行握手处理，可以设置onHandShake事件回调函数。
     * 函数原型为：
     * function onHandShake(swoole_http_request $request, swoole_http_response $response);
     *      onHandShake事件回调是可选的；
     *      设置onHandShake回调函数后不会再触发onOpen事件，需要应用代码自行处理；
     *      onHandShake函数必须返回true表示握手成功，返回其他值表示握手失败；
     *      内置的握手协议为Sec-WebSocket-Version: 13，低版本浏览器需要自行实现握手；
     *
     * @var callable onHandshake 事件回调函数
     * @internal
     */
    public $onHandshake;

    /**
     * __construct swoole_http_server 构造函数
     *
     * @since 3.1.0
     *
     * @param string $serv_host 服务要监听的 ip 地址
     * @param int    $serv_port 服务要监听的 port 端口
     *
     */
    public function __construct(string $serv_host, int $serv_port)
    {
    }

    /**
     * on
     *
     * @since 3.1.0
     *
     * @param $event_name
     * @param $callback
     *
     * ```php
     * swoole_http_server 事件列表
     * Start    master 进程，master 进程启动后调用
     * Shutdown master 进程，master 进程退出
     *
     * ManagerStart manager 进程，manager 进程启动
     * ManagerStop  manager 进程，manager 进程退出
     * WorkerError  manager 进程，worker 进程出错
     *
     * Close    worker 进程，客户端关闭连接事件
     * Error    worker 进程，客户端连接错误
     * Finish   worker 进程，task 进程调用 finish 接口或 return，worker 进程触发该事件
     *
     * WorkerStart worker/task 进程，worker/task 进程启动
     * WorkerStop  worker/task 进程，worker/task 进程退出
     * PipeMessage worker/task 进程，调用 $server->sendMessage 接口后，收到消息的进程触发该事件
     *
     * Task        task 进程，worker 进程向 task 进程发送数据
     *
     * Request   worker 进程，服务端收到一个完整的 http 请求后，调用此函数。onRequest 回调会销毁 $request 和 $response 对象
     * $http_server->on('request', function ($request, $response) {
     *      $response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>");
     * });
     *
     * swoole_websocket_server 独有事件：
     * HandShake    worker 进程，WebSocket建立连接后进行握手。WebSocket服务器已经内置了handshake，
     *                          如果用户希望自己进行握手处理，可以设置onHandShake事件回调函数。
     * onOpen       worker 进程，当WebSocket客户端与服务器建立连接并完成握手后会回调此函数。
     * onMessage    worker 进程，必须设置 onMessage 回调，当服务器收到来自客户端的数据帧时会回调此函数
     * ```
     *
     * @return
     */

    /**
     * 注册 Server 的事件回调函数
     *
     * @since 3.1.0
     *
     * @param string   $event_name 事件名，不区分大小写
     * ```php
     * swoole_server 事件列表
     * Start    master 进程，master 进程启动后触发该事件
     *          函数原型：function onStart(swoole_server $server);
     *                            在此事件之前Swoole Server已进行了如下操作：
     *                            已创建了manager进程。
     *                            已创建了worker子进程。
     *                            已监听所有TCP/UDP端口。
     *                            已监听了定时器。
     * Shutdown master 进程，master 进程退出后触发该事件
     *          函数原型：function onShutdown(swoole_server $server);
     *							 在此之前Swoole Server已进行了如下操作：
     *							 已关闭所有线程。
     *							 已关闭所有worker进程。
     *							 已close所有TCP/UDP监听端口。
     *							 已关闭主Rector。
     * ManagerStart manager 进程，manager 进程启动后触发该事件
     *              函数原型：function onManagerStart(swoole_server $serv);
     * 						 在这个回调函数中可以修改管理进程的名称
     * ManagerStop  manager 进程，manager 进程退出后触发该事件
     *              函数原型：function onManagerStop(swoole_server $serv);
     * WorkerError  manager 进程，worker 进程出错后触发该事件
     *              函数原型：function onWorkerError(
     *                                  swoole_server $serv,
     *                                  int $worker_id, int $worker_pid,
     *                                  int $exit_code, int $signal);
     *                               $worker_pid 是异常进程的ID。
     *                               $exit_code 退出的状态码，范围是 1 ～255。
     *                               $signal 进程退出的信号。
     * Connect  worker 进程，客户端连接后触发该事件
     *          函数原型：function onConnect(swoole_server $server,
     *                                     int $fd, int $from_id);
     *                           $server 是 swoole_server 对象。
     *                           $fd 是连接的文件描述符，发送数据/关闭连接时需要此参数。
     *                           $from_id 来自那个 Reactor 线程。
     * Receive  worker 进程，收到客户端发送的数据后触发该事件
     *          函数原型：function onReceive(swoole_server $server, int $fd,
     *                                     int $reactor_id, string $data);
     *                  $server 是 swoole_server 对象。
     *                  $fd 是连接的文件描述符，发送数据/关闭连接时需要此参数。
     *                  $from_id 来自那个 Reactor 线程。
     *                  $data 收到的数据
     *
     *                  //receive 事件回调示例
     *                  $server->on('Receive',
     *                               function($serv, $fd, $from_id, $data) {
     *                                $serv->send($fd, 'Swoole: '.$data);
     *                              });
     * Close    worker 进程，客户端关闭连接后触发该事件
     *          函数原型：function onClose(swoole_server $server,
     *                                   int $fd, int $reactorId);
     *                  		 $server 是swoole_server对象。
     *                  		 $fd 是连接的文件描述符。
     *                  		 $reactorId 来自那个reactor线程。
     * Packet   worker 进程，Udp server 收到客户端发送数据后触发该事件
     *          函数原型：function onPacket(swoole_server $server, string $data,
     *                                    array $client_info);
     *                  $server，swoole_server对象。
     *                  $data，收到的数据内容，可能是文本或者二进制内容。
     *                  $client_info，客户端信息包括address/port/server_socket 3项数据。
     *
     * Task     task 进程，worker 进程向 task 进程发送数据后触发该事件
     *          函数原型：function onTask(swoole_server $serv, int $task_id,
     *                                  int $src_worker_id, mixed $data);
     *                           $task_id       任务 id
     *                           $src_worker_id 发送数据的 worker_id
     *                           $data          要发送的数据
     *
     * Finish   worker 进程，task 进程调用 finish 接口或 return，worker 进程触发该事件
     *          函数原型：function onFinish(swoole_server $serv,
     *                                    int $task_id, string $data)；
     *                           $task_id是任务的ID，
     *                           $data是任务处理的结果内容。
     *
     * WorkerStart worker/task 进程，worker/task 进程启动后触发该事件
     *             函数原型：function onWorkerStart(swoole_server $server,
     *                                            int $worker_id)
     *             可以通过$worker_id参数的值，判断worker是普通worker还是task_worker。
     *             $worker_id>= $serv->setting['worker_num'] 时表示这个进程是task_worker。
     *             $worker_id是一个 [0, $worker_num] 之间的数字，
     *             表示这个worker进程的ID，$worker_id和进程PID没有任何关系
     * WorkerStop  worker/task 进程，worker/task 进程退出后触发该事件
     *             函数原型：function onWorkerStop(swoole_server $server,
     *                                           int $worker_id);
     *                               $worker_id 退出进程的 id 号
     * PipeMessage worker/task 进程，调用 $server->sendMessage 接口后，收到消息的进程触发
     *             函数原型：function onPipeMessage(swoole_server $server,
     *                                            int $from_worker_id,
     *                                            string $message);
     *                               $from_worker_id 消息来自哪个进程
     *                               $message 消息内容
     *
     * swoole_http_server 新增事件
     * Request   worker 进程，服务端收到一个完整的 http 请求后，调用此函数。
     *                       onRequest 回调会销毁 $request 和 $response 对象
     * $http_server->on('Request', function ($request, $response) {
     *      $response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>");
     * });
     *
     * swoole_websocket_server 独有事件：
     * HandShake worker 进程，WebSocket建立连接后进行握手。
     *                  WebSocket服务器已经内置了handshake，
     *                  如果用户希望自己进行握手处理，可以设置onHandShake事件回调函数。
     *           函数原型：function onHandShake(swoole_http_request $request,
     *                                        swoole_http_response $response);
     *           onHandShake事件回调是可选的；
     *           设置 onHandShake 回调函数后不会再触发 onOpen 事件，需要应用代码自行处理；
     *           onHandShake 函数必须返回true表示握手成功，返回其他值表示握手失败；
     *           内置的握手协议为Sec-WebSocket-Version: 13，低版本浏览器需要自行实现握手；
     *
     * onOpen   worker 进程，当WebSocket客户端与服务器建立连接并完成握手后会回调此函数。
     *          函数原型：function onOpen(swoole_websocket_server $svr,
     *                                  swoole_http_request $req);
     *                  $req 是一个Http请求对象，包含了客户端发来的握手请求信息；
     *                  onOpen 事件函数中可以调用push向客户端发送数据或者调用close关闭连接；
     *                  onOpen 事件回调是可选的；
     *
     * onMessage    worker 进程，必须设置onMessage回调，收到来自客户端的数据帧时会回调此函数
     *              函数原型：function onMessage(swoole_server $server,
     *                                         swoole_websocket_frame $frame);
     *              $frame 是swoole_websocket_frame对象，包含了客户端发来的数据帧信息；
     *              onMessage回调必须被设置，未设置服务器将无法启动；
     *              客户端发送的ping帧不会触发onMessage，底层会自动回复pong包；
     * ```
     * @param callable $callback 发生 $event_name 事件时的回调函数
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function on($event_name, $callback)
    {
    }

    /**
     * push  向对端推送数据，长度最大不得超过2M，只用于 websocket
     *
     * @since 3.1.0
     *
     * @param string $data 要发送的数据内容
     * @param int    $opcode [optional]  指定发送数据内容的格式，默认为文本 WEBSOCKET_OPCODE_TEXT_FRAME，值为 1。
     *                                   发送二进制内容$opcode参数需要设置为WEBSOCKET_OPCODE_BINARY_FRAME，值为 2。
     * @param bool   $finish [optional]  一个message分为多个frame传输时，finish为 fakse 表示 message 未完，为 true 代表最后一个frame。
     *
     * @return bool 执行成功返回 true，失败返回 false
     */
    public function push(string $data, int $opcode = 1, $finish = true)
    {
    }

    /**
     * exist 检测 $fd 对应的连接是否存在
     *
     * @since 3.1.0
     *
     * @param int $fd 要检测的连接的 $fd
     *
     * @return bool 连接存在返回 true，不存在返回 false
     */
    public function exist(int $fd)
    {
    }

    /**
     * start 启动 Http 服务器
     *
     * @since 3.1.0
     *
     * @return bool 启动成功返回 true，失败返回 false
     */
    public function start()
    {
    }

    /**
     * 增加一个Server要监听的端口
     *
     * @since 3.1.0
     *
     * @param string $host 要监听的ip地址
     * @param int    $port 要监听的端口
     * @param int    $sock_type socket类型，参考构造函数中的 $sock_type
     *
     * @return \swoole_server_port|false 成功返回 swoole_server_port 对象，失败返回 false
     */
    public function listen($host, $port, $sock_type)
    {
    }

    /**
     * 增加一个Server要监听的端口，意义同 listen 接口
     *
     * @since 3.1.0
     *
     * @param string $host 要监听的ip地址
     * @param int    $port 要监听的端口
     * @param int    $sock_type socket类型，参考构造函数中的 $sock_type
     *
     * @return \swoole_server_port|false 成功返回 swoole_server_port 对象，失败返回 false
     */
    public function addlistener($host, $port, $sock_type)
    {
    }

    /**
     * set 设置 swoole_websocket_server 的配置选项
     *
     * @since 3.1.0
     *
     * @param array $zset 数组，key->value 形式的配置项
     * ```php
     * 参考 swoole_server->set 中配置选项，http_server 额外增加了如下配置：
     * upload_tmp_dir  设置上传文件的临时目录
     * http_parse_post 设置POST消息解析开关，选项为true时自动将Content-Type
     *                 为x-www-form-urlencoded的请求包体解析到POST数组。
     *                 设置为false时将关闭POST解析。
     * ```
     * @return bool 成功返回true，失败返回 false
     */
    public function set($zset)
    {
    }

    /**
     * protect 从心跳管理中隔离连接，用于保护某些连接不被心跳线程切断
     *
     * @since 3.1.0
     *
     * @param  int  $conn_fd 要保护的连接 $fd
     * @param  bool $is_protected [optional] 可选参数，默认为 true
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function protect(int $conn_fd, bool $is_protected = null)
    {
    }

    /**
     * sendfile 发送文件到 TCP 客户端连接
     *
     * ```php
     *  sendfile 函数调用 OS 提供的 sendfile 系统调用，由操作系统直接读取文件并写入 socket。
     *  sendfile 只有2次内存拷贝，使用此函数可以降低发送大量文件时操作系统的 CPU 和内存占用。
     * ```
     *
     * @since 3.1.0
     *
     * @param int    $conn_fd 指定目的客户端
     * @param string $filename 要发送的文件路径，如果文件不存在会返回 false
     *
     * @return bool 操作成功返回 true，失败返回 false
     */
    public function sendfile($conn_fd, $filename)
    {
    }

    /**
     * close  关闭客户端连接
     *
     * @since 3.1.0
     *
     * @param int  $fd 要关闭的客户端连接
     * @param bool $reset [optional] 是否强制关闭，默认为否，设置为 true 会强制关闭连接，丢弃发送队列中的数据
     *
     * @return bool 操作成功返回 true，失败返回 false
     */
    public function close(int $fd, bool $reset = false)
    {
    }

    /**
     * task 非阻塞投递任务到task_worker 池子中，此函数是非阻塞的，执行完毕会立即返回，worker进程可以继续处理新的请求。
     *
     * @since 3.1.0
     *
     * @param mixed $data 非资源类型的任意 php 变量
     * @param int   $worker_id [optional]  可选参数，默认值为 -1。投递目标task_worker 进程id，默认 －1，设置范围 0 - (serv->task_worker_num -1)
     *
     * @return int|bool 调用成功，返回值为整数 $task_id，表示此任务的 ID。如果有 finish 回应，onFinish 回调中会携带 $task_id 参数
     *                  调用失败，返回 false
     */
    public function task($data, $worker_id)
    {
    }

    /**
     * 用于在 task 进程中通知 worker 进程，投递的任务已完成，此函数可以传递结果数据给 worker 进程
     *
     * ```php
     *  要在 task 进程中调用 finish 函数，必须为 Server 设置 onFinish 回调函数。
     *  此函数只可用于 task 进程的 onTask 回调中
     *  在 task 进程中，return 数据同样会触发 onFinish 事件。
     * ```
     *
     * @since 3.1.0
     *
     * @param mixed $data task 进程要传递给 worker 进程的数据
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function finish(mixed $data)
    {
    }

    /**
     * reload  重启所有worker 进程
     *
	 * ```
	 * Reload操作只能重新载入Worker进程启动后加载的PHP文件，建议使用get_included_files函数来列出哪些文件是在WorkerStart之前就加载的PHP文件，
	 * 在此列表中的PHP文件，即使进行了reload操作也无法重新载入。比如要关闭服务器重新启动才能生效。
	 * ```
	 *
     * @since 3.1.0
     *
     * @param bool $only_reload_taskworkrer [option] 是否仅重启 task 进程
     *
     * @return bool 调用成功返回 true，调用失败返回 false
     */
    public function reload()
    {
    }

    /**
     * shutdown  关闭服务器，可以用在worker 进程内。向主进程发送SGITERM也可以实现关闭服务器。
     *
     * @since 3.1.0
     *
     * @return void
     */
    public function shutdown()
    {
    }

    /**
     * stop  停止当前worker进程，并立即触发onWorkerStop回调函数，停止其它worker进程需要指定 $worker_id 的值。
     *
     * @since 3.1.0
     *
     * @param int $worker_id ［optional］可选参数，停止自己可以不用设置，如果要停止其它worker进程，需通过参数指定 $worker_id 指定进程号
     *
     * @return bool 调用成功返回 true，调用失败返回 false
     */
    public function stop()
    {
    }

    /**
     * getLastError 获取最近一次操作错误的错误码，业务代码中可以根据错误码类型执行不同的逻辑。
     *
	 * ```
	 *  常见发送失败错误
	 *  1001 连接已经被Server端关闭了，出现这个错误一般是代码中已经执行了$serv->close()关闭了某个连接，但仍然调用$serv->send()向这个连接发送数据
	 *  1002 连接已被Client端关闭了，Socket已关闭无法发送数据到对端
	 *  1003 正在执行close，onClose回调函数中不得使用$serv->send()
	 *  1004 连接已关闭
	 *	1005 连接不存在，传入$fd 可能是错误的
	 *	1007 接收到了超时的数据，TCP关闭连接后，可能会有部分数据残留在管道缓存区内，这部分数据会被丢弃
	 *	1008 发送缓存区已满无法执行send操作，出现这个错误表示这个连接的对端无法及时收数据导致发送缓存区已塞满
	 *	常见发送失败错误
	 * ```
	 *
     * @since 3.1.0
     *
     * @return int 返回一个整型数字错误码
     */
    public function getLastError()
    {
    }

    /**
     * 检测服务器所有连接，并找出已经超过约定时间的连接。
     * 如果if_close_connection=true，则自动关闭超时的连接。否则仅返回连接的fd数组。
     *
     * @since 3.1.0
     *
     * @param bool $if_close_connection [optional] 可选参数，默认为 false，
     *
     * @return void
     */
    public function heartbeat(bool $if_close_connection = false)
    {
    }

    /**
     * 该函数用来获取连接的信息
     *
     * @since 3.1.0
     *
     * @param int  $fd woker 进程的编号
     * @param int  $from_id [optional] 可选参数，若设置该参数，则只获取通过 reactor 线程 id 为 $from_id 的连接信息
     *
     * @return array|false 如果传入的 fd 存在，将会返回一个数组。如果传入的 fd 不存在或已关闭，返回 false。
     * ```
     * Array
     * (
     * [server_fd] => 3
     * [socket_type] => 1
     * [server_port] => 9501
     * [remote_port] => 56119
     * [remote_ip] => 127.0.0.1
     * [from_id] => 0
     * [connect_time] => 1495691486
     * [last_time] => 1495691486
     * )
     * ```
     */
    public function getClientInfo($fd, $from_id)
    {
    }

    /**
     * 遍历当前Server所有的客户端连接，getClientList 方法是基于共享内存的，不存在IOWait，遍历的速度很快。
     * 另外 getClientList 会返回所有 TCP 连接，而不仅仅是当前 worker 进程的 TCP 连接。
     *
     * @since 3.1.0
     *
     * @param int $start_fd [optional] 起始 $fd，默认是 0
     * @param int $find_count [optional] 每页取多少条，默认是 10，最大值不得超过 SW_MAX_FIND_COUNT，即100
     *
     * @return array|false 调用成功返回一个数字索引，元素是取到的 $fd。数组会按从小到大排序，
     *         最后一个 $fd 作为新的 start_fd 再次尝试获取，调用失败返回 false
     */
    public function getClientList($start_fd, $find_count)
    {
    }

    /**
     * denyRequest id 号为 $worker_id 的 worker 进程不再接收新的请求
     *
     * @since 3.1.0
     *
     * @param int $worker_id 指定worker 进程 id
     *
     * @return bool|void 失败返回 false
     */
    public function denyRequest($worker_id)
    {
    }

    /**
     * exit
     *
     * @since 3.1.0
     *
     * @return
     */
    //public function exit() {}

    /**
     * 向任意 worker 进程或着 tasker 进程发送消息。不能在主进程和管理进程中使用，收到消息的进程会触发 onPipeMessage 事件
     *
     * @since 3.1.0
     *
     * @param string $msg 要发送的消息内容
     * @param int    $work_id 目标进程 id，范围是0 ~ (worker_num + task_worker_num - 1)
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function sendMessage($msg, $work_id)
    {
    }

    /**
     * addProcess  添加一个用户自定义的工作进程
     *
     * @since 3.1.0
     *
     * @param swoole_process $process
     *
     * @return int|false 成功返回该工作进程的 worker id，失败返回false
     *
     */
    public function addProcess()
    {
    }

    /**
     * stats 统计当前server 活动tcp 的连接数／启动时间／接受、关闭次数等信息
     *
     * @since 3.1.0
     *
     * @return array 返回 server 的统计信息，示例如下：
     * ```php
     * Array
     * (
     * [start_time] => 1495680500
     * [connection_num] => 1
     * [accept_count] => 1
     * [close_count] => 0
     * [tasking_num] => 0
     * [request_count] => 0
     * [worker_request_count] => 0
     * )
     * ```
     */
    public function stats()
    {
    }

    /**
     * getSocket 获取监听套接字的socket句柄
     * 此方法需要依赖PHP的sockets扩展，并且编译swoole时需要开启--enable-sockets选项
     *
     * @since 3.1.0
     *
     * @param int $port [optional] 指定端口，不填表示不指定，则取第一个监听的套接字
     *
     * @return resource 返回 sockets 资源句柄
     */
    public function getSocket($port)
    {
    }

    /**
     * bind 将连接 $fd 绑定到用户自定义$uid,设置 dispatch_mode=5，设置此 $uid 值进行 hash 固定分配，
     * 可以保证某一个 $uid 的连接全部会分配到同一个Worker进程。
     *
	 * ```
	 * 在默认的dispatch_mode=2设置下，server会按照socket fd来分配连接数据到不同的worker。
	 * 因为fd是不稳定的，一个客户端断开后重新连接，fd会发生改变。这样这个客户端的数据就会被分配到别的Worker。
	 * 使用bind之后就可以按照用户定义的ID进行分配。即使断线重连，相同uid的TCP连接数据会被分配相同的Worker进程。
	 * ```
	 *
     * @since 3.1.0
     *
     * @param int $fd 要连接的文件描述符
     * @param int $uid 用户自定义的 UID
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function bind(int $fd, int $uid)
    {
    }

}
