<?php
$data = [
    'id' => 1,
    'title' => 'Tiêu đề',
    'content' => 'Nội dung'
];

extract($data);

echo $id; echo '<br/>';
echo $data['id'];

//foreach ($data as $key=>$value){
//    ${$key} = $value;
//}
//
//echo $id;