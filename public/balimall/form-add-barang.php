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
			<div class="input error">
				<input type="text" placeholder="Deposit Sewa">	
				<p class="error">
					Error message!
				</p>
			</div>
			<div class="input error">
				<select>
					<option>kategori</option>
					<option>test</option>
					<option>kategori</option>
					<option>test</option>
					<option>kategori</option>
					<option>test</option>
				</select>
			</div>
			<div class="input">
				<textarea placeholder="Deskripsi Barang"></textarea>
			</div>
			<div class="input">
				<textarea placeholder="Catatan Penyewaan"></textarea>
			</div>
			<div class="input">
				<p>Tambahkan 4 foto barang sewaan anda.</p>
				<div class="row">
					<div class="dc3 tc6 img-id">
						<div class="inner">
							<span class="badge green">Foto utama</span>
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
			</div>
		</div>
	</section>
</main>
<?php include('footer.php') ?>
	
