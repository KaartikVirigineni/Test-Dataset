namespace WarehouseHub.Models;

public class Inventory
{
    public int Id { get; set; }
    public int ProductId { get; set; }
    public int WarehouseId { get; set; }
    public int Quantity { get; set; }
    public int ReorderLevel { get; set; }
    public DateTime LastRestocked { get; set; } = DateTime.UtcNow;
    
    public Product Product { get; set; } = null!;
    public Warehouse Warehouse { get; set; } = null!;
}