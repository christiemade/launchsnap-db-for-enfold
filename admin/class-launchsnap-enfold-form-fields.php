<?php

class launchsnap_db_form extends avia_form {

  public function __construct( $params = [] ) {
    parent::__construct( $params );
  }

  public function create_elements( $form_fields  ) {
    if ( ! empty( $this->form_params['el-id'] ) ) {
      $custom_id = str_replace('id="','', $this->form_params['el-id']);
      $custom_id = str_replace('"','',$custom_id);
      trim($custom_id);
      $form_fields['page_title'] = [
        'label' => 'Page Title',
        'type'  => 'hidden',
        'id'    => 'page_name',
        'std'   => $custom_id,
        'value' => $custom_id
      ];
    }
    parent::create_elements( $form_fields );
  }
}