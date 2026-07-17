<?php

namespace App\core;

class Template{

    private $__content = null;

    public function run($content, $dataView=[]){


        $this->__content = $content;
        if (!empty($dataView)){
            extract($dataView);
        }


        //var_dump($this->__content);

//        echo '<pre>';
//        print_r($matches[0]);
//        echo '</pre>';


        $this->printEntities(); //hiển thị dưới dạng entities (thực thể)
        $this->printRaw(); //hiển thị bản gốc (có html)
        $this->beginPhp();
        $this->endPhp();
        $this->ifCondition();
        $this->foreachLoop();
        $this->forLoop();
        $this->whileLoop();

        eval(' ?>'.$this->__content.'<?php ');

        //return $this->__content;

    }

    public function printEntities(){

        preg_match_all('~{{\s*(.+?)\s*}}~is', $this->__content, $matches);

        if (!empty($matches[1])){
            foreach ($matches[1] as $key=>$value){
                $this->__content = str_replace($matches[0][$key], '<?php echo htmlentities('.$value.'); ?>', $this->__content);
            }
        }

    }

    public function printRaw(){
        preg_match_all('~{!!\s*(.+?)\s*!!}~is', $this->__content, $matches);

        if (!empty($matches[1])){
            foreach ($matches[1] as $key=>$value){
                $this->__content = str_replace($matches[0][$key], '<?php echo '.$value.'; ?>', $this->__content);
            }
        }
    }

    public function beginPhp(){
        preg_match_all('~@php~is', $this->__content, $matches);

        if (!empty($matches[0])){
            foreach ($matches[0] as $value){
                $this->__content = str_replace($value, '<?php ', $this->__content);
            }
        }
    }

    public function endPhp(){
        preg_match_all('~@endphp~is', $this->__content, $matches);

        if (!empty($matches[0])){
            foreach ($matches[0] as $value){
                $this->__content = str_replace($value, ' ?>', $this->__content);
            }
        }
    }

    /*
     * if ()
     *
     * */
    public function ifCondition(){

        //Xử lý trường hợp mở if
        preg_match_all('~@if\s*\((.+?)\)\s*$~im', $this->__content, $matches);

        if (!empty($matches[1])){
            foreach ($matches[1] as $key=>$value){
                $this->__content = str_replace($matches[0][$key], '<?php if ('.$value.'): ?>', $this->__content);
            }
        }

        //Xử lý trường hợp else
        preg_match_all('~@else\s*$~im', $this->__content, $matches);

        if (!empty($matches[0])){
            foreach ($matches[0] as $value){
                $this->__content = str_replace($value, '<?php else: ?>', $this->__content);
            }
        }

        //Xử lý trường hợp endif
        preg_match_all('~@endif\s*$~im', $this->__content, $matches);

        if (!empty($matches[0])){
            foreach ($matches[0] as $value){
                $this->__content = str_replace($value, '<?php endif; ?>', $this->__content);
            }
        }
    }

    public function forLoop(){

        //Xử lý mở for
        preg_match_all('~@for\s*\((.+?)\)\s*$~im', $this->__content, $matches);


        if (!empty($matches[1])){
            foreach ($matches[1] as $key=>$value){
                $this->__content = str_replace($matches[0][$key], '<?php for ('.$value.'): ?>', $this->__content);
            }
        }

        //Xử lý endfor
        preg_match_all('~@endfor$~im', $this->__content, $matches);

        if (!empty($matches[0])){
            foreach ($matches[0] as $value){
                $this->__content = str_replace($value, '<?php endfor; ?>', $this->__content);
            }
        }
    }

    public function whileLoop(){

        //Xử lý mở while
        preg_match_all('~@while\s*\((.+?)\)\s*$~im', $this->__content, $matches);


        if (!empty($matches[1])){
            foreach ($matches[1] as $key=>$value){
                $this->__content = str_replace($matches[0][$key], '<?php while ('.$value.'): ?>', $this->__content);
            }
        }

        //Xử lý endwhile
        preg_match_all('~@endwhile\s*$~im', $this->__content, $matches);

        if (!empty($matches[0])){
            foreach ($matches[0] as $value){
                $this->__content = str_replace($value, '<?php endwhile; ?>', $this->__content);
            }
        }
    }

    public function foreachLoop(){
        //Xử lý mở foreach
        preg_match_all('~@foreach\s*\((.+?)\)\s*$~im', $this->__content, $matches);


        if (!empty($matches[1])){
            foreach ($matches[1] as $key=>$value){
                $this->__content = str_replace($matches[0][$key], '<?php foreach ('.$value.'): ?>', $this->__content);
            }
        }

        //Xử lý endforeach
        preg_match_all('~@endforeach\s*$~im', $this->__content, $matches);


        if (!empty($matches[0])){
            foreach ($matches[0] as $value){
                $this->__content = str_replace($value, '<?php endforeach; ?>', $this->__content);
            }
        }
    }
}