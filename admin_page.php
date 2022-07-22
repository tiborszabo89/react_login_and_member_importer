<?php function admin_page_content() { ?>

  <div class="custom-admin-page-wrapper">
    <h2>
      Datenupload gewinnprofi.com
    </h2>
    <div class="member-input-wrapper" style="display:flex;">
      <form action="" class="new-member-import" enctype="multipart/form-data" method="post" style="border:1px solid #000; padding:30px; margin:15px;">
        <div class="new-user-wrap" style="display:flex; flex-direction:column;">
          <h3>Upload Aktiv_Input.csv</h3>
          <input type="file" name="new_users" id="new_users" multiple="false" accept=".xlsx, .csv">
          <input class="super-loader" type="submit" name="new_users_submit" value="Upload"/>
        </div>
      </form>

      <form action="" class="user-update-import" enctype="multipart/form-data" method="post" style="border:1px solid #000; padding:30px; margin:15px;">
        <div class="update-user-wrap" style="display:flex; flex-direction:column;">
          <h3>Upload Inaktiv_Sprung.csv</h3>
          <input type="file" name="update_users" id="update_users" multiple="false" accept=".xlsx, .csv">
          <input class="super-loader" type="submit" name="update_users_submit" value="Upload"/>
        </div>
      </form>
    </div>
  </div>


<?php
global $wpdb;

function xrange($start, $limit, $step = 1) {
  if ($start <= $limit) {
      if ($step <= 0) {
          throw new LogicException('Step must be positive');
      }

      for ($i = $start; $i <= $limit; $i += $step) {
          yield $i;
      }
  } else {
      if ($step >= 0) {
          throw new LogicException('Step must be negative');
      }

      for ($i = $start; $i >= $limit; $i += $step) {
          yield $i;
      }
  }
}

 if(isset($_POST["new_users_submit"])){

    $filename = $_FILES["new_users"]; 

    $movefile = wp_handle_upload( $filename, array(
      'test_form' => false,
      'mimes' => array('xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'csv' => 'text/csv'),
    ));
    if ( $movefile && !isset( $movefile['error'] ) ) {
      echo "File is valid, and was successfully uploaded.\n";
    } else {
      echo $movefile['error'];
      exit;
    }
    require 'vendor/autoload.php';

    if($movefile['type'] === "text/csv") :

      $csv_reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
  
      $csv_reader->setReadEmptyCells(false);
      $csv_reader->setDelimiter(',');
      $csv_reader->setEnclosure('"');
      $csv_reader->setSheetIndex(0);

      $csv_spreadsheet = $csv_reader->load($movefile['file']);

      $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($csv_spreadsheet);
      $writer->save(wp_upload_dir()['path'] . '/temp.xlsx');

      $csv_reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
      $spreadsheet = $csv_reader->load(wp_upload_dir()['path'] . '/temp.xlsx');


    elseif($movefile['type'] === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') :

      $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
      $reader->setReadEmptyCells(false); //Skip empty cell in rows
      $spreadsheet = $reader->load($movefile['file']);
    
    endif;

      $sheetData = $spreadsheet->getActiveSheet();
      
      $index = 2;
      $highestRow = $spreadsheet->getActiveSheet()->getHighestDataRow();

    foreach (xrange($index, $highestRow,1) as $key => $row) {

        $user_plz = $sheetData->getCell("L$index")->getValue();
        $user_city = $sheetData->getCell("M$index")->getValue();

        $user_location = $user_plz . ' , ' . $user_city; 

        $userdata = [
          'user_login'           => $sheetData->getCell("V$index")->getValue(),      
          'user_pass'            => $sheetData->getCell("AQ$index")->getValue(),
          'user_nicename'        => str_replace('%',' ',$sheetData->getCell("J$index")->getValue()),      
          'user_email'           => '', 
          'display_name'         => str_replace('%',' ',$sheetData->getCell("J$index")->getValue()),
          'nickname'             => str_replace('%',' ',$sheetData->getCell("J$index")->getValue()),
          'role'                 => '',     
          'meta_input'           => [
            'first_name'   => str_replace('%',' ',$sheetData->getCell("J$index")->getValue()),
            'subscription' => $sheetData->getCell("F$index")->getValue(),
            'sub_status'   => 'Aktiv',
            'city'         => $user_location,
          ],
        ];
        
        //Joseph magic
        wp_defer_term_counting( true );
        wp_defer_comment_counting( true );
        $wpdb->query( 'SET autocommit = 0;' );

        wp_insert_user( $userdata );

        $wpdb->query( 'COMMIT;' );
        wp_defer_term_counting( false );
        wp_defer_comment_counting( false );

        $index++;
      }
  }   

  // Update useres by kont number

  if(isset($_POST["update_users_submit"])){

    $filename = $_FILES["update_users"]; 

    $movefile = wp_handle_upload( $filename, array(
      'test_form' => false,
      'mimes' => array('xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'csv' => 'text/csv'),
    ));

    if ( $movefile && !isset( $movefile['error'] ) ) {
      echo "File is valid, and was successfully uploaded.\n";
    } else {
      echo $movefile['error'];
      exit;
    }
    require 'vendor/autoload.php';

    if($movefile['type'] === "text/csv") :

      $csv_reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
  
      $csv_reader->setReadEmptyCells(false);
      $csv_reader->setDelimiter(',');
      $csv_reader->setEnclosure('"');
      $csv_reader->setSheetIndex(0);

      $csv_spreadsheet = $csv_reader->load($movefile['file']);

      $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($csv_spreadsheet);
      $writer->save(wp_upload_dir()['path'] . '/temp.xlsx');

      $csv_reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
      $updatesheet = $csv_reader->load(wp_upload_dir()['path'] . '/temp.xlsx');

    elseif($movefile['type'] === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') :

      $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
      $reader->setReadEmptyCells(false); //Skip empty cell in rows
      $updatesheet = $reader->load($movefile['file']);
    
    endif;

      $updatedata = $updatesheet->getActiveSheet();
      $highestRow = $updatedata->getHighestDataRow();
    
    //Skip the first row
    $index = 2;

    foreach (xrange($index, $highestRow,1) as $key => $row) {
      
        $user_login = $updatedata->getCell("AH$index")->getValue();
      
        $userdata = [
          'user_login'  => preg_replace('/[^A-Za-z0-9\-]/','',strval($user_login)) ?? "",      
        ];

        //Get current user
        $current_user = get_user_by('login',$userdata['user_login']);
        $disable_subscription = "Inaktiv";
        update_user_meta( $current_user->ID, 'sub_status', $disable_subscription );

        $index++;
      }
  }   


?>

<?php } ?>