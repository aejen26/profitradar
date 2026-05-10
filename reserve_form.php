<form method="POST" action="reserve_stock.php">

<input type="hidden" name="product_id" value="<?=$id?>">

<label>Quantity</label>
<input type="number" name="quantity" required>

<label>Note</label>
<input type="text" name="note">

<button type="submit">Reserve</button>

</form>