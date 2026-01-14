<?php
/**
 * Plugin Name: Steel.ee Price Calculator
 * Description: Kiir-hinnakalkulaator + WPForms hidden field (ID=17)
 * Version: 1.1
 */

if (!defined('ABSPATH')) exit;

add_shortcode('steel_price_calc', function () {
  return '
  <div style="border:1px solid #ddd;padding:16px;border-radius:10px">
    <h3 style="margin:0 0 12px 0">Arvuta orienteeruv hind</h3>

    <label style="display:block;margin-bottom:8px">Pikkus (mm)<br>
      <input id="spc_l" type="number" min="1" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
    </label>

    <label style="display:block;margin-bottom:8px">Laius (mm)<br>
      <input id="spc_w" type="number" min="1" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
    </label>

    <label style="display:block;margin-bottom:12px">Kogus (tk)<br>
      <input id="spc_q" type="number" min="1" value="1" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
    </label>

    <button type="button" onclick="steelCalcPrice()"
      style="padding:10px 14px;border-radius:10px;border:0;cursor:pointer">
      Arvuta hind
    </button>

    <div style="margin-top:14px">
      <div id="spc_price" style="font-size:22px;font-weight:700">—</div>
      <div style="font-size:12px;opacity:.75;margin-top:6px">
        Orienteeruv hind. Täpne hind kinnitatakse pakkumisel.
      </div>
    </div>

    <script>
    function steelCalcPrice(){
      var l = Number(document.getElementById("spc_l").value || 0);
      var w = Number(document.getElementById("spc_w").value || 0);
      var q = Number(document.getElementById("spc_q").value || 1);

      if(l<=0 || w<=0 || q<=0){
        document.getElementById("spc_price").innerHTML = "Palun sisesta mõõdud ja kogus";
        return;
      }

      // DEMO hinnamudel (muudame hiljem sinu päris loogikaks)
      var m2 = (l/1000) * (w/1000);
      var price = (m2 * 25 * q + 5).toFixed(2);

      document.getElementById("spc_price").innerHTML = price.replace(".", ",") + " €";

      // ✅ WPForms Hidden Field ID = 17
      var wpformsFieldId = 17;
      var inputById = document.querySelector(\'input[name="wpforms[fields][\' + wpformsFieldId + \']"]\');
      if(inputById){
        inputById.value = price;
        inputById.dispatchEvent(new Event("change", {bubbles:true}));
      }

      // ✅ Varuvariant: kui kasutad CSS classi "steel-orient-hind"
      var inputByClass = document.querySelector("input.steel-orient-hind, .steel-orient-hind input");
      if(inputByClass){
        inputByClass.value = price;
        inputByClass.dispatchEvent(new Event("change", {bubbles:true}));
      }
    }
    </script>
  </div>';
});
