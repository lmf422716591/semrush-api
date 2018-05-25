<?php

namespace ScriptBurn\SemrushApi;

class KeyWordReport extends Analytics
{
    public function overview($keyword, $database, $options = [])
    {
        $options['extra_fields'] = ['type' => 'phrase_this'];
        return $this->fetchOverview('phrase_this', $keyword, $database, $options);
    }

    public function overviewAll($keywor, $options = [])
    {
        $options['extra_fields'] = ['type' => 'phrase_all'];
        return $this->fetchOverview('phrase_all', $keyword, "", $options);
    }

    private function fetchOverview($type, $keyword, $database = "", $options = [])
    {
        $keyword = array_values(array_filter(explode(PHP_EOL, trim($keyword))));
        $keyword = @$keyword[0];
        if (empty($keyword))
        {
            throw new \Exception('Invalid keyword');
        }
        elseif ($type == 'phrase_this' && !$database)
        {
            throw new \Exception('You must provide API database');
        }
        $params = ['phrase' => $keyword, 'type' => $type];
        $default_options = ['response_has_headers' => true, 'single_row' => false];
        $options = array_merge($default_options, $options);

        return $this->execAPI($params['type'], $params, $options);
    }



    public function related( $keyword, $database = "uk", $options = [])
    {
        $keyword = array_values(array_filter(explode(PHP_EOL, trim($keyword))));
        $keyword = @$keyword[0];
        if (empty($keyword))
        {
            throw new \Exception('Invalid keyword');
        }
        elseif ( !$database)
        {
            throw new \Exception('You must provide API database');
        }
        $params = ['phrase' => $keyword, 'type' => 'phrase_related'];
        $default_options = ['response_has_headers' => true, 'single_row' => false];
        $options = array_merge($default_options, $options);
        return $this->execAPI($params['type'], $params, $options);
    }
}
