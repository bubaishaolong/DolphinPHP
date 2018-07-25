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
namespace app \shop\admin;

use app\admin\controller\Admin;
use app\admin\model\Model as ModelModel;
use app\admin\model\Field as FieldModel;
use app\admin\model\Menu as MenuModel;
use app\common\builder\ZBuilder;
use think\Cache;
use think\Db;
use think\Request;

class  Databasetable extends Admin
{

    /**
     * 首页
     * @return mixed
     */
    public function index()
    {

        $request = Request::instance();
        $data = $request->dispatch();
        $mashu = $data['module'][0];

        // 查询
        //$map = $this->getMap();
        $map['name'] = $mashu;
        // 数据列表
        $data_list = ModelModel::where($map)->order('sort,id desc')->paginate();

        // 字段管理按钮
        $btnField = [
            'title' => '字段管理',
            'icon' => 'fa fa-fw fa-navicon',
            'href' => url('admin/field/index', ['id' => '__id__'])
        ];
        // 生成菜单节点
        $btnFieldNode = [
            'title' => '生成菜单节点',
            'icon' => 'glyphicon glyphicon-sort-by-attributes-alt',
            'href' => url('admin/fieldnode/index', ['group' => '__name__'])
        ];
        // 配置参数
        $btnFieldCof = [
            'title' => '配置管理',
            'icon' => 'glyphicon glyphicon-sort-by-attributes-alt',
            'href' => url('admin/moduleconfig/index', ['group' => $mashu])
        ];
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['name' => '标识', 'title' => '标题'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['icon', '图标', 'icon'],
                ['title', '表名', 'text'],
                ['name', '所属模块'],
                ['table', '数据表'],
                ['type', '模型', 'text', '', ['系统', '普通', '独立']],
                ['create_time', '创建时间', 'datetime'],
                ['sort', '排序', 'text'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            //->addValidate('ModelModel', 'title,sort') // 添加快捷编辑的验证器
            ->addFilter('type', ['系统', '普通', '独立'])
            ->addTopButtons(['back', 'add', 'custom' => $btnFieldCof])// 批量添加顶部按钮
            ->addRightButtons(['edit', 'custom' => $btnField, 'customnode' => $btnFieldNode, 'delete' => ['data-tips' => '删除模型将同时删除该模型下的所有字段，且无法恢复。']])// 批量添加右侧按钮
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
    }

    /**
     * 新增内容模型
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function add()
    {
        $request = Request::instance();
        $datas = $request->dispatch();
        $mashu = $datas['module'][0];
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            GenerateFile($data['table'], $mashu);
            $datamingzi = $mashu . "/" . convertUnderline($data['table']) . "/index";
            if ($data['table'] == '') {
                $data['table'] = config('database.prefix') . $mashu . '_' . $data['table'];
            } else {
                $data['table'] = config('database.prefix') . $mashu . '_' . $data['table'];
            }
            // 验证
            $result = $this->validate($data, 'Databasetable');
            if (true !== $result) $this->error($result);
            // 严格验证附加表是否存在
            if (table_exist($data['table'])) {
                $this->error('附加表已存在');
            }
            $data['name'] = $mashu;
            if ($model = ModelModel::create($data)) {
                // 创建附加表
                if (false === ModelModel::createTable($model)) {
                    $this->error('创建附加表失败');
                }
                // 创建菜单节点
//                $map = [
//                    'module' => $mashu,
//                    'pid' => $data['pid']
//                ];

                $menu_data = [
                    "module" => $mashu,
                    //"pid" => Db::name('admin_menu')->where($map)->value('id'),
                    "pid" => $data['pid'],
                    "title" => $data['title'],
                    "url_type" => "module_admin",
                    "url_value" => $datamingzi,
                    "url_target" => "_self",
                    "icon" => $data['icon'] ? $data['icon'] : "fa fa-fw fa-th-list",
                    "online_hide" => "0",
                    "sort" => "100",
                ];
                MenuModel::create($menu_data);

                // 记录行为

                Cache::clear();
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        $type_tips = '此选项添加后不可更改。如果为 <code>系统模型</code> 将禁止删除，对于 <code>独立模型</code>，将强制创建字段id,cid,uid,model,title,create_time,update_time,sort,status,trash,view';
        $datalists =Db::table('cj_admin_menu')->where(array('module'=>$mashu,'pid'=>0))->value('id');
        $dataarray = Db::table('cj_admin_menu')->where(array('pid'=>$datalists))->column('id,title');
        $dataarray[$datalists] ='顶级菜单';
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['static', 'name', '模型标识', '由小写字母、数字或下划线组成，不能以数字开头', 'shop'],
                ['text', 'title', '表名', '可填写中文'],
                ['text', 'table', '数据表', '创建后不可更改。由小写字母、数字或下划线组成，如果不填写默认为 <code>' . config('database.prefix') . $mashu . '_模型标识</code>，如果需要自定义，请务必填写系统表前缀，<code>#@__</code>表示当前系统表前缀'],
                ['radio', 'type', '模型类别', $type_tips, ['系统模型', '普通模型', '独立模型(不使用主表)'], 1],
                ['select','pid','选择上级菜单','',$dataarray],
                ['icon', 'icon', '图标'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1],
                ['text', 'sort', '排序', '', 100],
            ])
            ->fetch();
    }

    /**
     * 编辑内容模型
     * @param null $id 模型id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id === null) $this->error('参数错误');
        $request = Request::instance();
        $datas = $request->dispatch();
        $mashu = $datas['module'][0];
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'Databasetable.edit');
            if (true !== $result) $this->error($result);

            if (ModelModel::update($data)) {
                cache('admin_model_list', null);
                cache('admin_model_title_list', null);
                // 记录行为
                //action_log('databasetable_edit', $mashu.'_edit', $id, UID, "ID({$id}),标题({$data['title']})");
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $list_model_type = ['系统模型', '普通模型', '独立模型(不使用主表)'];

        // 模型信息
        $info = ModelModel::get($id);
        $info['type'] = $list_model_type[$info['type']];

        // 显示编辑页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['hidden', 'name'],
                ['static', 'name', '模型标识'],
                ['static', 'type', '模型类别'],
                ['static', 'table', '附加表'],
                ['text', 'title', '模型标题', '可填写中文'],
                ['icon', 'icon', '图标'],
                ['radio', 'status', '立即启用', '', ['否', '是']],
                ['text', 'sort', '排序'],
            ])
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 删除内容模型
     * @param null $ids 内容模型id
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed|void
     */
    public function delete($ids = null)
    {
        if ($ids === null) $this->error('参数错误');

        $model = ModelModel::where('id', $ids)->find();
        $datapp = explode(config('database.prefix').$model['name'].'_',$model['table']);
        if($datapp[1]){
            DeleteCorrespondingFile('shop',$datapp[1]);
        }
        if ($model['type'] == 0) {
            $this->error('禁止删除系统模型');
        }
        // 删除表和字段信息
        if (ModelModel::deleteTable($ids)) {
            // 删除主表中的文档
            if (false === Db::name('admin_model')->where('id', $ids)->delete()) {
                $this->error('删除主表文档失败');
            }
            // 删除字段数据
            if (false !== Db::name('admin_field')->where('model', $ids)->delete()) {
                cache(config('database.prefix') . 'model_list', null);
                cache(config('database.prefix') . 'model_title_list', null);
                $request = Request::instance();
                $data = $request->dispatch();
                $module = $data['module'][0];
                //删除菜单的列
                $datamingzi = $module . "/{$model['table']}/index";
                if (false !== Db::name('admin_menu')->where('url_value', $datamingzi)->delete()) {
                    //删除对用的文件及文件夹
                    if($datapp[1]){
                        DeleteCorrespondingFile('shop',$datapp[1]);
                    }
                    $this->success('删除成功', 'index');
                }

            } else {
                $this->error('删除内容模型字段失败');
            }
        } else {
            $this->error('删除内容模型表失败');
        }
    }

}