<?php include('metadata.php') ?>
<?php include('file-nav.php') ?>
<?php include('header-home.php') ?>
<div class="red-box"></div>
<main class="pdl">
	<section class="brc">
		<div class="container">
			<span class="mobile"><a href="">Balimall</a></span><span class="mobile"><i class="fas fa-chevron-right"></i></span>
			<span class="current mobile"><a href="">Dashboard</a></span>
		</div>
	</section>
	<section>
		<div class="container">
			<div class="box">
				<div class="row">
					<div class="dc3 desktop-only">
						<div class="sidebar">
							<div class="box">
								<div class="sidebar__profile">
									<figure>
										<img src="img/profile.png">
									</figure>
									<h5>Clara Kent</h5>
								</div>
								<div class="sidebar__group">
									<h6>Personal Information</h6>
									<div class="db-nav">
										<div class="input">
											<a href="">
												<i class="fas fa-home"></i> Dashboard
											</a>
										</div>
										<div class="input">
											<a href="">
												<i class="fas fa-cog"></i> Edit Profile
											</a>
										</div>
										<div class="input">
											<a href="/user/message" class=""><i class="fas fa-envelope"></i> Pesan</a>
										</div>
										<div class="input">
											<a href="">
												<i class="fas fa-map-marker-alt"></i> Shipment Address
											</a>
										</div>
									</div>
								</div>
								<hr>
								<div class="sidebar__group">
									<h6>Transaction</h6>
									<div class="db-nav">
										<div class="input">
											<a href="">
												<i class="fas fa-list"></i> Transaction History
											</a>
										</div>
										<div class="input">
											<a href="">
												<i class="far fa-check-circle"></i> Payment Confirmation
											</a>
										</div>
									</div>
								</div>
								<hr>
								<div class="sidebar__group">
									<div class="db-nav">
										<div class="input">
											<a href=""><i class="fas fa-sign-out-alt"></i> Logout</a>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="dc9 tc12">
						<div class="sub-title">
							<div class="text">
								<h3>Payment Confirmation</h3>
							</div>
							<div class="clear"></div>
						</div>
						<div class="input">
							<label>Nomor Invoice</label>
							<select>
								<option>Inv 1</option>
								<option>Inv 2</option>
							</select>
						</div>
						<div class="input">
							<label>Bank</label>
							<select>
								<option>Bank A</option>
								<option>Bank B</option>
							</select>
						</div>
						<div class="input">
							<label>Nama Akun Bank</label>
							<input type="text" placeholder="Nama Akun Bank">
						</div>
						<div class="input">
							<label>Nomor Rekening</label>
							<input type="text" placeholder="Nomor Rekening">
						</div>
						<div class="input">
							<div class="row">
								<div class="dc3 tc6 img-id">
									<div class="inner">
										<span class="badge">Bukti Transaksi</span>
										<a href="" class="fas fa-trash-alt"></a>
										<a href="" class="fas fa-edit"></a>
										<!-- <img src="img/item.jpg"> -->
										<a href="" class="add-img">
											<span>+</span>
										</a>
									</div>
								</div>
							</div>
						</div>
						<div class="input">
							<button class="sBtn red">Kirim Bukti Transfer</button>
						</div>
					</div>
				</div>
			</div>	
		</div>
	</section>
</main>
<?php include('footer.php') ?>
	
