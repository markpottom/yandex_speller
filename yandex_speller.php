<?php
/*
Plugin Name: Яндекс спеллер
Plugin URI: https://tech.yandex.ru/speller/
Description: Проверяет орфографию на сайте
Version: 1.0
Author: Марк Потягайло
*/
?>
<?php


add_action( 'wp_footer', 'test_init');
function test_init(){
 
	if ( is_user_logged_in() && current_user_can('administrator') ){

$dictionary_option = get_option( 'speller_option_name' );
//["custom_dictionary"]

	
  // что-то сделать
 	 //echo 'Вы сейчас авторизованы на сайте как администратор!';
	 $currenturl =  get_bloginfo('url').$_SERVER["REQUEST_URI"]; 
	 $text = file_get_contents($currenturl);

	if (preg_match('~<body[^>]*>(.*?)</body>~si', $text, $body)){
		$text_s =  $body[1];
		}
	
	$text_s = preg_replace ("/[^А-Яа-я -\s\xc2\xa0]/u","",$text_s);
	//print '<pre>'; print_r($text_s); print '</pre>';	 
	
	$ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"http://speller.yandex.net/services/spellservice.json/checkText");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,
            "text=".$text_s."&format=html&options=1");


curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$server_output = curl_exec ($ch);

curl_close ($ch);


$results = json_decode($server_output); 

print '<script>';
print 'obj = JSON.parse(JSON.stringify('.$server_output.'));';
print 'console.log(obj);';
//print 'var b= "|Ассута|ассута";';
print ' var b= "'.$dictionary_option['custom_dictionary'].'";';
print 'jQuery.each(obj, function(i, val) {';
//print '    console.log(obj[i].word);';
//print 'alert("ddddd");';

print 'var reg = new RegExp(obj[i].word,"g"); ';
print ' var repl =   "|"+obj[i].word+"|";';
print 'if(b.indexOf(repl) == -1){';
print 'b =b+"|"+ obj[i].word;';

print' var tool = "нет вариантов"; if(obj[i].s.length>0){ tool = obj[i].s[0];} ';

print 'document.body.innerHTML = document.body.innerHTML.replace(reg, "'; print "<span style=color:red data-title='"; print '"+tool+"'; print "'data-placement='right' rel='tooltip'>";
print '"+obj[i].word+"';
print "</span> "; print '"); } /* if*/ ';
 print "jQuery('[rel="; print '"tooltip"]'; print"').tooltip();";
print '     }); ';


//print 'console.log(b);';
print '</script>';

//print '<pre>'; print_r($results); print '</pre>';	
	
	} 
    
}
?>
<?php
class MarkSettingsPageSpeller
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            'Yandex speller', 
            'manage_options', 
            'yandex_speller_setting', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'speller_option_name' );
        ?>
        <div class="wrap">
            
            <h2>Настройки яндекс спеллер</h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'speller_option_group' );   
                do_settings_sections( 'yandex_speller_setting' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'speller_option_group', // Option group
            'speller_option_name', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'Настройки словаря', // Title
            array( $this, 'print_section_info' ), // Callback
            'yandex_speller_setting' // Page
        );  
		
		 add_settings_field(
            'custom_dictionary', 
            'Пользовательский словарь', 
            array( $this, 'custom_dictionary_callback' ), 
            'yandex_speller_setting', 
            'setting_section_id'
        );   
		
        add_settings_field(
            'id_number', // ID
            'ID Number', // Title 
            array( $this, 'id_number_callback' ), // Callback
            'yandex_speller_setting', // Page
            'setting_section_id' // Section           
        );      

          
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['id_number'] ) )
            $new_input['id_number'] = absint( $input['id_number'] );

        if( isset( $input['custom_dictionary'] ) )
            $new_input['custom_dictionary'] = sanitize_text_field( $input['custom_dictionary'] );

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Добавьте слова в пользовательский словарь отделяя их знаком "|":';
    }

    

    /** 
     * Get the settings option array and print one of its values
     */
    public function custom_dictionary_callback()
    {
        printf(
            '<textarea rows="10" cols="45" name="speller_option_name[custom_dictionary]">%s</textarea>',
            isset( $this->options['custom_dictionary'] ) ? esc_attr( $this->options['custom_dictionary']) : ''
        );
    }
	
	/** 
     * Get the settings option array and print one of its values
     */
    public function id_number_callback()
    {
        printf(
            '<input type="text" id="id_number" name="speller_option_name[id_number]" value="%s" />',
            isset( $this->options['id_number'] ) ? esc_attr( $this->options['id_number']) : ''
        );
    }
	
}

if( is_admin() )
    $my_settings_page = new MarkSettingsPageSpeller();
?>