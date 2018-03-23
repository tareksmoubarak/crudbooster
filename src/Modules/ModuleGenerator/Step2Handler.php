<?php

namespace crocodicstudio\crudbooster\Modules\ModuleGenerator;

use crocodicstudio\crudbooster\helpers\Parsers\ScaffoldingParser;
use CRUDBooster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class Step2Handler
{
    private $hooks = ['hookQueryIndex', 'hookRowIndex', 'hookBeforeAdd', 'hookAfterAdd',
        'hookBeforeEdit', 'hookAfterEdit', 'hookBeforeDelete', 'hookAfterDelete',];

    public function showForm($id)
    {
        $module = DB::table('cms_moduls')->where('id', $id)->first();

        $columns = CRUDBooster::getTableColumns($module->table_name);

        $controllerCode = (readCtrlContent($module->controller));

        $data = [];
        $data['id'] = $id;
        $data['columns'] = $columns;
        //$data['table_list'] = \CB::listCbTables();
        $data['cols'] = ScaffoldingParser::parse($controllerCode, 'col');


        foreach($this->hooks as $hook){
            $data[$hook] = FileManipulator::readMethodContent($controllerCode, $hook);
        }

        return view('CbModulesGen::step2', $data);
    }

    public function handleFormSubmit()
    {
        $id = Request::input('id');
        $controller = DB::table('cms_moduls')->where('id', $id)->first()->controller;

        $newCode = $this->makeColumnPhpCode();
        $code = readCtrlContent($controller);
        $fileResult = \CB::replaceBetweenMark($code, 'COLUMNS', $newCode);

        foreach($this->hooks as $hook){
            $fileResult = FileManipulator::writeMethodContent($fileResult, $hook, request($hook));
        }

        putCtrlContent($controller, $fileResult);

        return redirect()->route("AdminModulesControllerGetStep3", ["id" => $id]);
    }
    /**
     * @return array
     */
    private function makeColumnPhpCode()
    {
        $labels = request('column');
        $name = request('name');
        $isImage = request('is_image');
        $isDownload = request('is_download');
        $callback = request('callback');
        $width = request('width');

        $columnScript = [];
        $columnScript[] = '            $this->col[] = [];';
        foreach ($labels as $i => $label) {

            if (! $name[$i]) {
                continue;
            }

            $colProperties = ["'label' => '$label'", "'name' => '{$name[$i]}'"];
            if ($isImage[$i]) {
                $colProperties[] = '"image" => true ';
            }
            if ($isDownload[$i]) {
                $colProperties[] = '"download" => true';
            }
            if ($callback[$i]) {
                $colProperties[] = '"callback" => function($row) {'.$callback[$i].'}';
            }
            if ($width[$i]) {
                $colProperties[] = "'width' => '$width[$i]'";
            }

            $columnScript[] = '            $this->col[] = ['.implode(", ", $colProperties).'];';
        }
        return implode("\n", $columnScript);
    }
}