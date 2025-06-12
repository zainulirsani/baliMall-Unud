<?php include('metadata.php') ?>
<?php include('file-nav.php') ?>
<?php include('header.php') ?>
<main class="form-page">
	<section>
		<div class="container fc">	
			<h2>Tambah Barang</h2>
			<div class="input">
				<input type="text" placeholder="Nama Barang">	
			</div>
			<div class="input">
				<input type="text" placeholder="Harga Sewa">	
			</div>
			<div class="input">
				<input type="text" placeholder="Deposit Sewa">	
			</div>
			<div class="input">
				<input type="text" placeholder="Kategori">	
			</div>
			<div class="input">
				<textarea placeholder="Kategori"></textarea>
			</div>
			<div class="input">
				<textarea placeholder="Catatan Penyewaan"></textarea>
			</div>
			<div class="input">
				<figure class="img-wrapper mi">
					<span class="badge green">Foto utama</span>
					<a href="">
						<span class="inner">
							<span>+</span>Foto Barang
						</span>
					</a>
				</figure>
				<figure class="img-wrapper">
					<a href="">
						<img src="img/produk.jpg">
					</a>
				</figure>
				<figure class="img-wrapper">
					<a href="">
						<img src="img/produk.jpg">
					</a>
				</figure>
				<figure class="img-wrapper">
					<a href="">
						<img src="img/produk.jpg">
					</a>
				</figure>
				<figure class="img-wrapper">
					<a href="">
						<img src="img/produk.jpg">
					</a>
				</figure>
				<div class="clear"></div>
			</div>
			<div class="input">
				<input type="submit" value="Simpan" class="bBtn">
			</div>
		</div>
	</section>
</main>
<?php include('footer.php') ?>
	
