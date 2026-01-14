<?php
/**
 * Plugin Name: Steel.ee Price Calculator
 * Description: Hinnakalkulaator Steel.ee toodetele + WPForms hidden field tugi
 * Version: 1.0
 */

add_shortcode('steel_price_calc', function(){
return '
<div style="border:1px solid #ccc;padding:20px;border-radius:8px">
<h3>Arvuta orienteeruv hind</h3>

<label>Pikkus (mm)<br><input id="l"></label><br>
<label>Laius (mm)<br><input id="w"></label><br>
<label>Kogus<br><input id="q" value="1"></label><br><br>

<button onclick="calc()">Arvuta hind</button>

<h2 id="p">—</h2>

<script>
function calc(){
 let l=document.getElementById("l").value;
 let w=document.getElementById("w").value;
 let q=document.getElementById("q").value;

 let m2=(l/1000)*(w/1000);
 let price=(m2*25*q+5).toFixed(2);

 document.getElementById("p").innerHTML=price+" €";

 // WPForms hidden field
 let h = document.querySelector("input.steel-orient-hind, .steel-orient-hind input");
if(h) h.value = price;
}
</script>
</div>';
});
