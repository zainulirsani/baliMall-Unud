<div class="push"></div>
</div>
<footer>
	<div class="container">
		<div class="logo">
			<img src="img/balimall.png">
		</div>
		<div class="links">
			<ul>
				<li>
					<a href="">Kebijakan dan Privasi</a>
				</li>
				<li>
					<a href="">Syarat & Ketentuan</a>
				</li>
				<li>
					<a href="">Menjadi Merchant</a>
				</li>
				<li>
					<a href="">FAQ</a>
				</li>
				<li>
					<a href="">Cara Berbelanja di BaliMall</a>
				</li>
			</ul>
		</div>
		<div class="links">
			<ul>
				<li class="hub">
					<a href="">Hubungi Kami</a>
				</li>
			</ul>
			<ul class="contact-info">
				<p><span>BaliMall</span></p>
				
				<li>
					Hp:<a href=""> 0811 3116 4999</a>
				</li>
				<li>
					Email:<a href=""> info@balimall.id</a>
				</li>
				<li>
					Alamat Kantor:<a href=""> Jl. M. Yamin IX No. 19 Denpasar, Bali</a>
				</li>
				<br>
			</ul>
			<ul class="contact-info">
				<p><span>Layanan Ditjen</span></p>
				<li>
					<a href="">Layanan Pengaduan Konsumen Ditjen PKTN</a>
				</li>
				<li>
					Whatsapp:<a href=""> 0853 1111 1010</a>
				</li>
			</ul>
		</div>
		<div class="social">
			<div class="inner">
				<p>Ikuti berita kami di media sosial</p>
				<div class="btn-wrapper">
					<a href="" class="fab fa-facebook-square"></a>
					<a href="" class="fab fa-twitter-square"></a>
					<a href="" class="fab fa-instagram"></a>
				</div>
			</div>
		</div>
		<div class="clear"></div>
	</div>
	<div class="bot">
		<div class="container">
			Copyrights <b>BALIMALL 2020</b>	
		</div>
	</div>
</footer>
<div class="loading" style="display: ">
	<div class="inner">
		<div>
			<img src="img/balimall.png">
			<span>loading</span>
		</div>
	</div>
</div>
</body>
<div class="popup general" style="" title="general">
	<div class="wh100">
		<div class="popup-wrapper">
			<div class="inner">
				<a href="javascript:void(0)" class="close-btn" onclick="$(this).parents('.popup').fadeOut()"></a>
				<h3>Terima kasih sudah berlangganan!</h3>
				<p>Untuk mempersiapkan layanan kami, kami mohon saudara/i mengisi kueasioner kami di tautan berikut</p>
				<div class="btn-wrapper">
					<a href="">Ini link</a>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="popup general" style="display: " title="general">
	<div class="wh100">
		<div class="popup-wrapper">
			<div class="inner">
				<a href="javascript:void(0)" class="close-btn" onclick="$(this).parents('.popup').fadeOut()"></a>
				<p class="ct">	
					Apakah anda yakin ingin logout?
				</p>
				<div class="btn-wrapper">
					<a href="" class="sBtn red">Tidak</a> <a href="" class="gBtn red">Ya</a>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="popup uc" title="under construction" style="display: "> 
	<div class="wh100">
		<div class="popup-wrapper">
			<div class="inner">
				<!-- <a href="javascript:void(0)" class="close-btn" onclick="$(this).parents('.popup').fadeOut()"></a> -->
				<img src="img/404-popup.jpg">
				<div class="text">
					<h3>Halaman Sedang di Bangun</h3>
					<p>
						Mohon maaf, kenyamanan anda terganggu.</br>
						Segoedang akan kembali segera!
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="popup general" style="display: " title="popup form">
	<div class="wh100">
		<div class="popup-wrapper">
			<div class="inner">
				<a href="javascript:void(0)" class="close-btn" onclick="$(this).parents('.popup').fadeOut()"></a>
				<h3>Terima kasih sudah berlangganan!</h3>
				<p>Untuk mempersiapkan layanan kami, kami mohon saudara/i mengisi kueasioner kami di tautan berikut</p>
				<div class="input">
					<input type="text" name="">
				</div>
				<div class="input">
					<textarea></textarea>
				</div>
				<div class="btn-wrapper">
					<a href="" class="sBtn blue">SUBMIT</a>

					<a href="" class="sBtn blue">KEMBALI</a>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="popup adjust" style="display: ;" title="adjust">
	<div class="wh100">
		<div class="popup-wrapper">
			<div class="inner">
				<a href="javascript:void(0)" class="close-btn" onclick="$(this).parents('.popup').fadeOut()"></a>
				<h3>Edit Gambar</h3>
				<div class="image-adjust">
					<img id="image" src="img/user-pict.jpg" alt="Picture">
				</div>
				<div class="btn-wrapper">
					<a href="" class="sBtn red">Selesai</a></a>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
     window.addEventListener('DOMContentLoaded', function () {
		var image = document.querySelector('#image');
		var cropper = new Cropper(image, {
			dragMode: 'move',
			aspectRatio: 4 / 3,
			autoCropArea: 0.65,
			center: false,
			highlight: false,
		});
    });
  </script>
</html>
