using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using ReserveIt.Models;
using ReserveIt.Services;
using System.Security.Claims;

namespace ReserveIt.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize]
public class ReservationsController : ControllerBase
{
    private readonly IReservationService _reservationService;

    public ReservationsController(IReservationService reservationService)
    {
        _reservationService = reservationService;
    }

    [HttpPost]
    public async Task<ActionResult<ReservationResponse>> CreateReservation([FromBody] CreateReservationRequest request)
    {
        var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var response = await _reservationService.CreateReservation(userId, request);

        if (response == null)
        {
            return BadRequest(new { message = "Unable to create reservation. Table may be unavailable or already booked." });
        }

        return CreatedAtAction(nameof(GetReservation), new { id = response.Id }, response);
    }

    [HttpGet("{id}")]
    public async Task<ActionResult<ReservationResponse>> GetReservation(int id)
    {
        var reservation = await _reservationService.GetReservation(id);
        if (reservation == null)
        {
            return NotFound();
        }

        var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var userRole = User.FindFirstValue(ClaimTypes.Role);

        if (reservation.UserId != userId && userRole != "Admin")
        {
            return Forbid();
        }

        return Ok(reservation);
    }

    [HttpGet("my")]
    public async Task<ActionResult<List<ReservationResponse>>> GetMyReservations()
    {
        var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var reservations = await _reservationService.GetUserReservations(userId);
        return Ok(reservations);
    }

    [HttpGet]
    [Authorize(Policy = "AdminOnly")]
    public async Task<ActionResult<List<ReservationResponse>>> GetAllReservations()
    {
        var reservations = await _reservationService.GetAllReservations();
        return Ok(reservations);
    }

    [HttpPut("{id}")]
    public async Task<ActionResult<ReservationResponse>> UpdateReservation(int id, [FromBody] UpdateReservationRequest request)
    {
        var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var userRole = User.FindFirstValue(ClaimTypes.Role)!;

        var response = await _reservationService.UpdateReservation(id, userId, userRole, request);
        if (response == null)
        {
            return NotFound();
        }

        return Ok(response);
    }

    [HttpDelete("{id}")]
    public async Task<ActionResult> DeleteReservation(int id)
    {
        var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var userRole = User.FindFirstValue(ClaimTypes.Role)!;

        var success = await _reservationService.DeleteReservation(id, userId, userRole);
        if (!success)
        {
            return NotFound();
        }

        return NoContent();
    }
}