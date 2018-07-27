<?php
// +----------------------------------------------------------------------
// | PHP框架 [ ThinkPHP ]
// +----------------------------------------------------------------------
// | 版权所有 为开源做努力
// +----------------------------------------------------------------------
// | 时间: 2018-07-06 09:42:56
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
namespace app\shop\validate;

use think\Validate;

class  Menber extends Validate
{
    //定义验证规则
    // 定义验证规则
    
   protected $rule = [
  'status|状态' => NULL,
  'delete_time|删除时间' => NULL,
  'update_time|更新时间' => NULL,
  'create_time|创建时间' => NULL,
  'id|id' => NULL,
  'user_token|用户' => NULL,
  'title|标题' => NULL,
  'liste|列表' => NULL,
];
    //定义验证提示
    protected $message = [
        'name.regex' => '模型标识由小写字母、数字或下划线组成，不能以数字开头',
        'table.regex' => '附加表由小写字母、数字或下划线组成，不能以数字开头',
    ];

}