<?php include('metadata.php') ?>
<button?php include('file-nav.php') ?>
<button?php include('header.php') ?>
<main class="form-page">
	<section>
		<div class="container fc">	
			<h2>Registrasi</h2>
			<div class="input">
				<div class="pfl-pic ct">	
					<a href="">
						<img src="img/user.jpg">
						<span>+</span>
					</a>
				</div>
			</div>
			<div class="input">
				<input type="text" placeholder="Nama Sesuai Identitas">	
			</div>
			<div class="input">
				<input type="text" placeholder="Email">	
			</div>
			<div class="input">
				<input type="text" placeholder="No. Telp">	
			</div>
			<div class="input">
				<textarea placeholder="Alamat"></textarea>
			</div>
			<div class="input">
				<div class="maps">
					
				</div>
			</div>
			<div class="input">
				<input type="text" placeholder="Cari di maps">
			</div>
			<div class="input">
				<p>5 Foto Kartu Identitas dengan jenis yang berbeda</p>
				<div class="row">
					<div class="dc3 tc6 img-id">
						<div class="inner">
							<a href="" class="fas fa-trash-alt"></a>
							<a href="" class="fas fa-edit"></a>
							<img src="img/item.jpg">
							<a href="" class="add-img">
								<span>+</span>
							</a>
						</div>
					</div>
					<div class="dc3 tc6 img-id">
						<div class="inner">
							<a href="" class="fas fa-trash-alt"></a>
							<a href="" class="fas fa-edit"></a>
							<img src="img/item.jpg">
							<a href="" class="add-img">
								<span>+</span>
							</a>
						</div>
					</div>
					<div class="dc3 tc6 img-id">
						<div class="inner">
							<a href="" class="fas fa-trash-alt"></a>
							<a href="" class="fas fa-edit"></a>
							<img src="img/item.jpg">
							<a href="" class="add-img">
								<span>+</span>
							</a>
						</div>
					</div>
				</div>
			</div>
			<div class="input">
				<input type="submit" value="Simpan" class="sBtn blue">
				<div class="right-side">
					<p>
						Sudah memiliki akun SEGOEDANG? <a href="">Login di sini</a>
					</p>
				</div>
				<div class="clear"></div>
			</div>
			<div class="input btn-dd">
				<div class="dropdown">
					<button class="dropbtn" onclick="myFunction()" type="submit">
						Daftar
						<i class="fa fa-caret-down"></i>
					</button>
					<div id="myDropdown" class="dropdown-content">
						<button href="#">Pedagang</button>
						<button href="#" class="subdrop-btn">Pembeli <i class="fa fa-caret-down"></i></button>
						<div id="mySubDropdown" class="sub-dropdown-content">
							<button href="#">Pembeli B2C</button>
							<button href="#">Pembeli B2G</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</main>

<script>
	function myFunction() {
	document.getElementById("myDropdown").classList.toggle("show");
	}
	
	window.onclick = function(event) {
		if (!event.target.matches('.dropbtn')) {
			var dropdowns = document.getElementsByClassName("dropdown-content");
			var i;
			for (i = 0; i < dropdowns.length; i++) {
				var openDropdown = dropdowns[i];
				if (openDropdown.classList.contains('show')) {
				openDropdown.classList.remove('show');
				}
			}
		}
	}
</script>
<?php include('footer.php') ?>
	
