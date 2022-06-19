/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.scss in this case)
import './styles/app.scss';


//import './lib/flaticon/font/flaticon.css';
import './lib/owlcarousel/assets/owl.carousel.min.css';
import './lib/lightbox/css/lightbox.min.css';
import './css/style.css';

var $ = require( "jquery" );
window.$ = $;
window.jQuery = $;

// bootstrap 5
import { Tooltip, Toast, Popover } from "bootstrap";

import 'font-awesome/css/font-awesome.min.css';

import './lib/owlcarousel/owl.carousel.min';
import './lib/isotope/isotope.pkgd.min';
import './lib/lightbox/js/lightbox.min';

import './js/main';

// start the Stimulus application
import './bootstrap';
