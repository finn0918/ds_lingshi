<?php

class WordSplite
{
    const to_be_more = '...';

    private static function splite_single_utf8_left_word($str)
    {
        $aciss = ord($str);
        $out_str = '';
        
        if ($aciss >= 240)
        {
            $out_str .= substr($str, 0, 4);
        }
        elseif ($aciss >= 224)
        {
            $out_str .= substr($str, 0, 3);
        }
        elseif ($aciss >= 192)
        {
            $out_str .= substr($str, 0, 2);
        }
        else
        {
            $out_str .= substr($str, 0, 1);
        }
        return $out_str;
    }

    private static function splite_single_utf8_right_word($str)
    {
        $aciss = ord($str);
        $out_str = '';
        
        if ($aciss >= 240)
        {
            $out_str .= substr($str, 4);
        }
        elseif ($aciss >= 224)
        {
            $out_str .= substr($str, 3);
        }
        elseif ($aciss >= 192)
        {
            $out_str .= substr($str, 2);
        }
        else
        {
            $out_str .= substr($str, 1);
        }
        
        return $out_str;
    }

    public static function count_word($str, $length = 0)
    {
        $aciss = ord($str);
        
        if ($aciss >= 240)
        {
            $length += 1;
            $str = substr($str, 4);
        }
        elseif ($aciss >= 224)
        {
            $length += 1;
            $str = substr($str, 3);
        }
        elseif ($aciss >= 192)
        {
            $length += 1;
            $str = substr($str, 2);
        }
        else
        {
            $length += 1;
            $str = substr($str, 1);
        }
        
        if ($str == '')
        {
            return $length;
        }
        else
        {
            return self::count_word($str, $length);
        }
    }

    public function splite_mulit_utf8_word($str, $start = 0, $length = -1)
    {
        
        $temp = '';
        
        if ($start < 0)
        {
            $start = self::count_word($str) + $start;
        }
        
        for ($i = 0; $i < $start; $i ++)
        {
            $str = self::plite_single_utf8_right_word($str);
        }
        
        for ($i = 0; $i < $length; $i ++)
        {
            $temp .= self::splite_single_utf8_left_word($str);
            $str = self::splite_single_utf8_right_word($str);
        }
        
        if ($length == - 1)
        {
            return $str;
        }
        else
        {
            return $temp;
        }
    }

    public static function buildSpliteWord($str, $start = 0, $length = -1)
    {
        $more = '';
        if ($length < self::count_word($str))
        {
            $more = self::to_be_more;
        }
        
        $content = self::splite_mulit_utf8_word($str, $start, $length) . $more;
        return $content;
    }
}

?>